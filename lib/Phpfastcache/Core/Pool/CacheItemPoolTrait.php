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
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Entities\ItemBatch;
use Phpfastcache\Event\Event;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
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

    public function __construct(ConfigurationOptionInterface $config, string $instanceId, EventManagerInterface $em)
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
        $collection = [];

        foreach ($keys as $key) {
            $collection[$key] = $this->getItem($key);
        }

        return $collection;
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
            $this->validateCacheKey($key);

            $cacheSlamsSpendSeconds = 0;

            $itemClass = self::getItemClass();
            /** @var $item ExtendedCacheItemInterface */
            $item = new $itemClass($this, $key, $this->eventManager);
            // $item = new (self::getItemClass())($this, $key, $this->eventManager);
            // Uncomment above when this one will be fixed: https://github.com/phpmd/phpmd/issues/952

            getItemDriverRead:
            {
                $driverArray = $this->driverRead($item);

            if ($driverArray) {
                $driverData = $this->driverUnwrapData($driverArray);

                if ($this->getConfig()->isPreventCacheSlams()) {
                    while ($driverData instanceof ItemBatch) {
                        if ($driverData->getItemDate()->getTimestamp() + $this->getConfig()->getCacheSlamsTimeout() < \time()) {
                            /**
                             * The timeout has been reached
                             * Consider that the batch has
                             * failed and serve an empty item
                             * to avoid get stuck with a
                             * batch item stored in driver
                             */
                            goto getItemDriverExpired;
                        }

                        $this->eventManager->dispatch(Event::CACHE_GET_ITEM_IN_SLAM_BATCH, $this, $driverData, $cacheSlamsSpendSeconds);

                        /**
                         * Wait for a second before
                         * attempting to get exit
                         * the current batch process
                         */
                        \sleep(1);
                        $cacheSlamsSpendSeconds++;

                        goto getItemDriverRead;
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

                getItemDriverExpired:
                $this->handleExpiredCacheItem($item);
            } else {
                $item->expiresAfter((int) abs($this->getConfig()->getDefaultTtl()));
            }
            }
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
        $return = true;
        foreach ($keys as $key) {
            $result = $this->deleteItem($key);
            if ($result !== true) {
                $return = false;
            }
        }

        return $return;
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
        if ($item->isHit() && $this->driverDelete($item)) {
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

            return true;
        }

        return false;
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
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
                if ($result !== true) {
                    unset($this->deferredList[$key]);
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

        $this->eventManager->dispatch(Event::CACHE_SAVE_ITEM, $this, $item);

        if ($this->getConfig()->isPreventCacheSlams()) {
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
            $item->setHit(true);
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
    protected function deregisterItem(string $item): static
    {
        unset($this->itemInstances[$item]);

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

    protected function validateCacheKey(string $key): void
    {
        if (\preg_match('~([' . \preg_quote(self::$unsupportedKeyChars, '~') . ']+)~', $key, $matches)) {
            throw new PhpfastcacheInvalidArgumentException(
                'Unsupported key character detected: "' . $matches[1] . '". 
                    Please check: https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%5D-Unsupported-characters-in-key-identifiers'
            );
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
            $this->driverDelete($item);

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
}
