<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

declare(strict_types=1);

namespace Phpfastcache\Core\Pool;

use DateTime;
use Phpfastcache\Config\ConfigurationOptionInterface;
use Phpfastcache\Config\IOConfigurationOptionInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Entities\ItemBatch;
use Phpfastcache\Event\Event;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Exceptions\PhpfastcacheUnsupportedMethodException;
use Psr\Cache\CacheItemInterface;
use RuntimeException;

/**
 * @method string[] driverUnwrapTags(array $wrapper)
 * @method void cleanItemTags(ExtendedCacheItemInterface $item)
 */
trait CacheItemPoolTrait
{
    use DriverBaseTrait {
        DriverBaseTrait::__construct as __driverBaseConstruct;
    }

    /**
     * @var string
     */
    protected static string $unsupportedKeyChars = '{}()/\@:';

    /**
     * @var ExtendedCacheItemInterface[]|CacheItemInterface[]
     */
    protected array $deferredList = [];

    /**
     * @var ExtendedCacheItemInterface[]|CacheItemInterface[]
     */
    protected array $itemInstances = [];

    protected DriverIO $IO;

    public function __construct(#[\SensitiveParameter] ConfigurationOptionInterface $config, string $instanceId, EventManagerInterface $em)
    {
        $this->IO = new DriverIO();
        $this->__driverBaseConstruct($config, $instanceId, $em);
    }

    /**
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setItem(CacheItemInterface $item): static
    {
        if (self::getItemClass() === $item::class) {
            if (!$this->getConfig()->isUseStaticItemCaching()) {
                throw new PhpfastcacheLogicException(
                    'The static item caching option (useStaticItemCaching) is disabled so you cannot attach an item.'
                );
            }

            $this->itemInstances[$item->getKey()] = $item;

            return $this;
        }
        throw new PhpfastcacheInvalidArgumentException(
            \sprintf(
                'Invalid cache item class "%s" for driver "%s".',
                get_class($item),
                get_class($this)
            )
        );
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];

        /**
         * Usually, drivers that are able to enable cache slams
         * does not benefit of driverReadMultiple() call.
         */
        if (!$this->getConfig()->isPreventCacheSlams()) {
            $this->validateCacheKeys(...$keys);

            /**
             * Check for local item instances first.
             */
            foreach ($keys as $index => $key) {
                if (isset($this->itemInstances[$key]) && $this->getConfig()->isUseStaticItemCaching()) {
                    $items[$key] = $this->itemInstances[$key];
                    // Key already exists in local item instances, no need to fetch it again.
                    unset($keys[$index]);
                }
            }
            $keys = array_values($keys);

            /**
             * If there's still keys to fetch, let's choose the right method (if supported).
             */
            if (count($keys) > 1) {
                $items = array_merge(
                    array_combine($keys, array_map(fn($key) => new (self::getItemClass())($this, $key, $this->eventManager), $keys)),
                    $items
                );

                try {
                    $driverArrays = $this->driverReadMultiple(...$items);
                } catch (PhpfastcacheUnsupportedMethodException) {
                    /**
                     * Fallback for drivers that does not yet implement driverReadMultiple() method.
                     */
                    $driverArrays = array_combine(
                        array_map(fn($item) => $item->getKey(), $items),
                        array_map(fn($item) => $this->driverRead($item), $items)
                    );
                } finally {
                    foreach ($items as $item) {
                        $driverArray = $driverArrays[$item->getKey()] ?? null;
                        if ($driverArray !== null) {
                            $item->set($this->driverUnwrapData($driverArray));
                            $item->expiresAt($this->driverUnwrapEdate($driverArray));
                            if ($this->getConfig()->isItemDetailedDate()) {
                                /**
                                 * If the itemDetailedDate has been
                                 * set after caching, we MUST inject
                                 * a new DateTime object on the fly
                                 */
                                $item->setCreationDate($this->driverUnwrapCdate($driverArray) ?: new DateTime());
                                $item->setModificationDate($this->driverUnwrapMdate($driverArray) ?: new DateTime());
                            }
                            $item->setTags($this->driverUnwrapTags($driverArray));
                            $this->handleExpiredCacheItem($item);
                        } else {
                            $item->expiresAfter((int) abs($this->getConfig()->getDefaultTtl()));
                        }
                        $item->isHit() ? $this->getIO()->incReadHit() : $this->getIO()->incReadMiss();
                    }
                }
            } else {
                $index = array_key_first($keys);
                if ($index !== null) {
                    $items[$keys[$index]] = $this->getItem($keys[$index]);
                }
            }
        } else {
            $collection = [];

            foreach ($keys as $key) {
                $collection[$key] = $this->getItem($key);
            }

            return $collection;
        }

        $this->eventManager->dispatch(Event::CACHE_GET_ITEMS, $this, $items);

        return $items;
    }

    /**
     * @param string $key
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.GotoStatement)
     */
    public function getItem(string $key): ExtendedCacheItemInterface
    {
        /**
         * Replace array_key_exists by isset
         * due to performance issue on huge
         * loop dispatching operations
         */
        if (!isset($this->itemInstances[$key]) || !$this->getConfig()->isUseStaticItemCaching()) {
            $this->validateCacheKeys($key);

            /** @var $item ExtendedCacheItemInterface */
            $item = new (self::getItemClass())($this, $key, $this->eventManager);

            $getItemDriverRead = function (float $cacheSlamsSpendSeconds = 0) use (&$getItemDriverRead, $item): void {
                $config = $this->getConfig();

                $driverArray = $this->driverRead($item);

                if ($driverArray) {
                    $driverData = $this->driverUnwrapData($driverArray);

                    if ($config instanceof IOConfigurationOptionInterface && $config->isPreventCacheSlams()) {
                        while ($driverData instanceof ItemBatch) {
                            if ($driverData->getItemDate()->getTimestamp() + $config->getCacheSlamsTimeout() < \time()) {
                                /**
                                 * The timeout has been reached
                                 * Consider that the batch has
                                 * failed and serve an empty item
                                 * to avoid get stuck with a
                                 * batch item stored in driver
                                 */
                                $this->handleExpiredCacheItem($item);
                                return;
                            }

                            $this->eventManager->dispatch(Event::CACHE_GET_ITEM_IN_SLAM_BATCH, $this, $driverData, $cacheSlamsSpendSeconds);

                            /**
                             * Wait for a second before
                             * attempting to get exit
                             * the current batch process
                             */
                            \usleep(100000);

                            $getItemDriverRead($cacheSlamsSpendSeconds + 0.1);
                        }
                    }

                    $item->set($driverData);
                    $item->expiresAt($this->driverUnwrapEdate($driverArray));

                    if ($this->getConfig()->isItemDetailedDate()) {
                        /**
                         * If the itemDetailedDate has been
                         * set after caching, we MUST inject
                         * a new DateTime object on the fly
                         */
                        $item->setCreationDate($this->driverUnwrapCdate($driverArray) ?: new DateTime());
                        $item->setModificationDate($this->driverUnwrapMdate($driverArray) ?: new DateTime());
                    }

                    $item->setTags($this->driverUnwrapTags($driverArray));
                    $this->handleExpiredCacheItem($item);
                } else {
                    $item->expiresAfter((int) abs($this->getConfig()->getDefaultTtl()));
                }
            };
            $getItemDriverRead();
        } else {
            $item = $this->itemInstances[$key];
        }

        $this->eventManager->dispatch(Event::CACHE_GET_ITEM, $this, $item);

        $item->isHit() ? $this->getIO()->incReadHit() : $this->getIO()->incReadMiss();

        return $item;
    }

    /**
     * @param string $key
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function hasItem(string $key): bool
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheIOException
     */
    public function clear(): bool
    {
        $this->eventManager->dispatch(Event::CACHE_CLEAR_ITEM, $this, $this->itemInstances);

        $this->getIO()->incWriteHit();
        // Faster than detachAllItems()
        $this->itemInstances = [];

        return $this->driverClear();
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function deleteItems(array $keys): bool
    {
        if (count($keys) > 1) {
            $return = true;
            try {
                $items = $this->getItems($keys);
                $return = $this->driverDeleteMultiple($keys);
                foreach ($items as $item) {
                    $item->setHit(false);

                    if (!\str_starts_with($item->getKey(), TaggableCacheItemPoolInterface::DRIVER_TAGS_KEY_PREFIX)) {
                        $this->cleanItemTags($item);
                    }
                }
                $this->getIO()->incWriteHit();
                $this->eventManager->dispatch(Event::CACHE_DELETE_ITEMS, $this, $items);
                $this->deregisterItems($keys);
            } catch (PhpfastcacheUnsupportedMethodException) {
                foreach ($keys as $key) {
                    $result = $this->deleteItem($key);
                    if ($result !== true) {
                        $return = false;
                    }
                }
            }

            return $return;
        }

        $index = array_key_first($keys);
        if ($index !== null) {
            return $this->deleteItem($keys[$index]);
        }

        return false;
    }

    /**
     * @param string $key
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function deleteItem(string $key): bool
    {
        $item = $this->getItem($key);
        if ($item->isHit()) {
            $result = $this->driverDelete($item->getKey(), $item->getEncodedKey());
            $item->setHit(false);
            $this->getIO()->incWriteHit();

            $this->eventManager->dispatch(Event::CACHE_DELETE_ITEM, $this, $item);

            /**
             * De-register the item instance
             * then collect gc cycles
             */
            $this->deregisterItem($key);

            /**
             * Perform a tag cleanup to avoid memory leaks
             */
            if (!\str_starts_with($key, TaggableCacheItemPoolInterface::DRIVER_TAGS_KEY_PREFIX)) {
                $this->cleanItemTags($item);
            }

            return $result;
        }

        return false;
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, self::getItemClass());
        if (!\array_key_exists($item->getKey(), $this->itemInstances)) {
            $this->itemInstances[$item->getKey()] = $item;
        } elseif (\spl_object_hash($item) !== \spl_object_hash($this->itemInstances[$item->getKey()])) {
            throw new RuntimeException('Spl object hash mismatches ! You probably tried to save a detached item which has been already retrieved from cache.');
        }

        $this->eventManager->dispatch(Event::CACHE_SAVE_DEFERRED_ITEM, $this, $item);
        $this->deferredList[$item->getKey()] = $item;

        return true;
    }

    /**
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function commit(): bool
    {
        $this->eventManager->dispatch(Event::CACHE_COMMIT_ITEM, $this, new EventReferenceParameter($this->deferredList));

        if (\count($this->deferredList)) {
            $return = true;
            foreach ($this->deferredList as $key => $item) {
                $result = $this->save($item);
                unset($this->deferredList[$key]);
                if ($result !== true) {
                    $return = $result;
                }
            }

            return $return;
        }
        return false;
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function save(CacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, self::getItemClass());
        /**
         * @var ExtendedCacheItemInterface $item
         *
         * Replace array_key_exists by isset
         * due to performance issue on huge
         * loop dispatching operations
         */
        if (!isset($this->itemInstances[$item->getKey()])) {
            if ($this->getConfig()->isUseStaticItemCaching()) {
                $this->itemInstances[$item->getKey()] = $item;
            }
        } elseif (\spl_object_hash($item) !== \spl_object_hash($this->itemInstances[$item->getKey()])) {
            throw new RuntimeException('Spl object hash mismatches ! You probably tried to save a detached item which has been already retrieved from cache.');
        }

        $this->assertCacheItemType($item, self::getItemClass());
        $this->eventManager->dispatch(Event::CACHE_SAVE_ITEM, $this, $item);

        if ($this->getConfig() instanceof IOConfigurationOptionInterface && $this->getConfig()->isPreventCacheSlams()) {
            /**
             * @var $itemBatch ExtendedCacheItemInterface
             */
            $itemClassName = self::getItemClass();
            $itemBatch = new $itemClassName($this, $item->getKey(), $this->eventManager);
            $itemBatch->set(new ItemBatch($item->getKey(), new DateTime()))
                ->expiresAfter($this->getConfig()->getCacheSlamsTimeout());

            /**
             * To avoid SPL mismatches
             * we have to re-attach the
             * original item to the pool
             */
            $this->driverWrite($itemBatch);
            $this->detachItem($itemBatch);
            $this->attachItem($item);
        }


        if ($this->driverWrite($item) && $this->driverWriteTags($item)) {
            $item->setHit(true)
                ->clearRemovedTags();

            if ($this->getConfig()->isItemDetailedDate()) {
                $item->setModificationDate(new \DateTime());
            }

            $this->getIO()->incWriteHit();

            return true;
        }

        return false;
    }

    /**
     * @return DriverIO
     */
    public function getIO(): DriverIO
    {
        return $this->IO;
    }

    /**
     * @internal This method de-register an item from $this->itemInstances
     */
    protected function deregisterItem(string $itemKey): static
    {
        unset($this->itemInstances[$itemKey]);

        if (\gc_enabled()) {
            \gc_collect_cycles();
        }

        return $this;
    }

    /**
     * @param string[] $itemKeys
     * @internal This method de-register multiple items from $this->itemInstances
     */
    protected function deregisterItems(array $itemKeys): static
    {
        $this->itemInstances = array_diff_key($this->itemInstances, array_flip($itemKeys));

        if (\gc_enabled()) {
            \gc_collect_cycles();
        }

        return $this;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function attachItem(CacheItemInterface $item): static
    {
        if (isset($this->itemInstances[$item->getKey()]) && \spl_object_hash($item) !== \spl_object_hash($this->itemInstances[$item->getKey()])) {
            throw new PhpfastcacheLogicException(
                'The item already exists and cannot be overwritten because the Spl object hash mismatches ! 
                You probably tried to re-attach a detached item which has been already retrieved from cache.'
            );
        }

        if (!$this->getConfig()->isUseStaticItemCaching()) {
            throw new PhpfastcacheLogicException(
                'The static item caching option (useStaticItemCaching) is disabled so you cannot attach an item.'
            );
        }

        $this->itemInstances[$item->getKey()] = $item;

        return $this;
    }

    public function isAttached(CacheItemInterface $item): bool
    {
        if (isset($this->itemInstances[$item->getKey()])) {
            return \spl_object_hash($item) === \spl_object_hash($this->itemInstances[$item->getKey()]);
        }
        return false;
    }

    protected function validateCacheKeys(string ...$keys): void
    {
        foreach ($keys as $key) {
            if (\preg_match('~([' . \preg_quote(self::$unsupportedKeyChars, '~') . ']+)~', $key, $matches)) {
                throw new PhpfastcacheInvalidArgumentException(
                    'Unsupported key character detected: "' . $matches[1] . '". 
                    Please check: https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%5D-Unsupported-characters-in-key-identifiers'
                );
            }
        }
    }

    protected function handleExpiredCacheItem(ExtendedCacheItemInterface $item): void
    {
        if ($item->isExpired()) {
            /**
             * Using driverDelete() instead of delete()
             * to avoid infinite loop caused by
             * getItem() call in delete() method
             * As we MUST return an item in any
             * way, we do not de-register here
             */
            $this->driverDelete($item->getKey(), $item->getEncodedKey());

            /**
             * Reset the Item
             */
            $item->set(null)
                ->expiresAfter((int) abs($this->getConfig()->getDefaultTtl()))
                ->setHit(false)
                ->setTags([]);

            if ($this->getConfig()->isItemDetailedDate()) {
                /**
                 * If the itemDetailedDate has been
                 * set after caching, we MUST inject
                 * a new DateTime object on the fly
                 */
                $item->setCreationDate(new DateTime());
                $item->setModificationDate(new DateTime());
            }
        } else {
            $item->setHit(true);
        }
    }

    /**
     * @param ExtendedCacheItemInterface[] $items
     * @param bool $encoded
     * @param string $keyPrefix
     * @return string[]
     */
    protected function getKeys(array $items, bool $encoded = false, string $keyPrefix = ''): array
    {
        return array_map(
            static fn(ExtendedCacheItemInterface $item) => $keyPrefix . ($encoded ? $item->getEncodedKey() : $item->getKey()),
            $items
        );
    }
}
