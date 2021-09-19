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
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Entities\ItemBatch;
use Phpfastcache\Event\EventManagerDispatcherTrait;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Util\ClassNamespaceResolverTrait;
use Psr\Cache\CacheItemInterface;
use ReflectionClass;
use ReflectionObject;
use RuntimeException;

trait CacheItemPoolTrait
{
    use DriverBaseTrait;

    /**
     * @var string
     */
    protected static string $unsupportedKeyChars = '{}()/\@:';

    /**
     * @var ExtendedCacheItemInterface[]
     */
    protected array $deferredList = [];

    /**
     * @var ExtendedCacheItemInterface[]
     */
    protected array $itemInstances = [];

    protected DriverIO $IO;

    /**
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setItem(CacheItemInterface $item): static
    {
        if ($this->getItemClass() === $item::class) {
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
     * @param array $keys
     * @return array
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
            if (\preg_match('~([' . \preg_quote(self::$unsupportedKeyChars, '~') . ']+)~', $key, $matches)) {
                throw new PhpfastcacheInvalidArgumentException(
                    'Unsupported key character detected: "' . $matches[1] . '". 
                    Please check: https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%5D-Unsupported-characters-in-key-identifiers'
                );
            }

            $cacheSlamsSpendSeconds = 0;
            $itemClass = self::getItemClass();
            /** @var $item ExtendedCacheItemInterface */
            $item = new $itemClass($this, $key, $this->eventManager);

            getItemDriverRead:
            {
                $driverArray = $this->driverRead($item);

            if ($driverArray) {
                if (!\is_array($driverArray)) {
                    throw new PhpfastcacheCoreException(
                        sprintf(
                            'The driverRead method returned an unexpected variable type: %s',
                            \gettype($driverArray)
                        )
                    );
                }
                $driverData = $this->driverUnwrapData($driverArray);

                if ($this->getConfig()->isPreventCacheSlams()) {
                    while ($driverData instanceof ItemBatch) {
                        if ($driverData->getItemDate()->getTimestamp() + $this->getConfig()->getCacheSlamsTimeout() < \time()) {
                            /**
                             * The timeout has been reached
                             * Consider that the batch has
                             * failed and serve an empty item
                             * to avoid to get stuck with a
                             * batch item stored in driver
                             */
                            goto getItemDriverExpired;
                        }
                        /**
                         * @eventName CacheGetItem
                         * @param $this ExtendedCacheItemPoolInterface
                         * @param $driverData ItemBatch
                         * @param $cacheSlamsSpendSeconds int
                         */
                        $this->eventManager->dispatch('CacheGetItemInSlamBatch', $this, $driverData, $cacheSlamsSpendSeconds);

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
                        ->expiresAfter(abs((int)$this->getConfig()->getDefaultTtl()))
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
            } else {
                $item->expiresAfter(abs($this->getConfig()->getDefaultTtl()));
            }
            }
        } else {
            $item = $this->itemInstances[$key];
        }


        if ($item !== null) {
            /**
             * @eventName CacheGetItem
             * @param $this ExtendedCacheItemPoolInterface
             * @param $this ExtendedCacheItemInterface
             */
            $this->eventManager->dispatch('CacheGetItem', $this, $item);

            $item->isHit() ? $this->getIO()->incReadHit() : $this->getIO()->incReadMiss();

            return $item;
        }
        throw new PhpfastcacheInvalidArgumentException(\sprintf('Item %s was not build due to an unknown error', \gettype($key)));
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
        /**
         * @eventName CacheClearItem
         * @param $this ExtendedCacheItemPoolInterface
         * @param $itemInstances ExtendedCacheItemInterface[]
         */
        $this->eventManager->dispatch('CacheClearItem', $this, $this->itemInstances);

        $this->getIO()->incWriteHit();
        // Faster than detachAllItems()
        $this->itemInstances = [];

        return $this->driverClear();
    }

    /**
     * @param array $keys
     * @return bool
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

            /**
             * @eventName CacheCommitItem
             * @param $this ExtendedCacheItemPoolInterface
             * @param $item ExtendedCacheItemInterface
             */
            $this->eventManager->dispatch('CacheDeleteItem', $this, $item);

            /**
             * De-register the item instance
             * then collect gc cycles
             */
            $this->deregisterItem($key);

            /**
             * Perform a tag cleanup to avoid memory leaks
             */
            if (!\str_starts_with($key, self::DRIVER_TAGS_KEY_PREFIX)) {
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

        /**
         * @eventName CacheSaveDeferredItem
         * @param $this ExtendedCacheItemPoolInterface
         * @param $this ExtendedCacheItemInterface
         */
        $this->eventManager->dispatch('CacheSaveDeferredItem', $this, $item);
        $this->deferredList[$item->getKey()] = $item;

        return true;
    }

    /**
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function commit(): bool
    {
        /**
         * @eventName CacheCommitItem
         * @param $this ExtendedCacheItemPoolInterface
         * @param $deferredList ExtendedCacheItemInterface[]
         */
        $this->eventManager->dispatch('CacheCommitItem', $this, new EventReferenceParameter($this->deferredList));

        if (\count($this->deferredList)) {
            $return = true;
            foreach ($this->deferredList as $key => $item) {
                $result = $this->save($item);
                if ($return !== true) {
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

        /**
         * @eventName CacheSaveItem
         * @param $this ExtendedCacheItemPoolInterface
         * @param $this ExtendedCacheItemInterface
         */
        $this->eventManager->dispatch('CacheSaveItem', $this, $item);


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
}
