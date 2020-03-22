<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Core\Pool;

use DateTime;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Exceptions\{PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException};
use Psr\Cache\{CacheItemInterface};

/**
 * Trait TaggableCacheItemPoolTrait
 * @package Phpfastcache\Core\Pool
 * @method ExtendedCacheItemInterface getItem(string $key) Return the config object
 * @method ExtendedCacheItemInterface[] getItems(array $keys) Return the config object
 */
trait TaggableCacheItemPoolTrait
{
    /**
     * @inheritdoc
     */
    public function getItemsByTagsAsJsonString(array $tagNames, int $option = 0, int $depth = 512, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): string
    {
        $callback = static function (CacheItemInterface $item) {
            return $item->get();
        };

        return \json_encode(\array_map($callback, \array_values($this->getItemsByTags($tagNames, $strategy))), $option, $depth);
    }

    /**
     * @inheritdoc
     */
    public function getItemsByTags(array $tagNames, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): array
    {
        $items = [];
        foreach (\array_unique($tagNames) as $tagName) {
            if (\is_string($tagName)) {
                $items[] = $this->fetchItemsByTagFromBackend($tagName);
            } else {
                throw new PhpfastcacheInvalidArgumentException('$tagName must be a a string');
            }
        }

        $items = \array_merge([], ...$items);

        switch ($strategy) {
            case TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL:
                foreach ($items as $key => $item) {
                    if (\array_diff($tagNames, $item->getTags())) {
                        unset($items[$key]);
                    }
                }
                break;

            case TaggableCacheItemPoolInterface::TAG_STRATEGY_ONLY:
                foreach ($items as $key => $item) {
                    if (\array_diff($tagNames, $item->getTags()) || \array_diff($item->getTags(), $tagNames)) {
                        unset($items[$key]);
                    }
                }
                break;
        }
        return $items;
    }

    /**
     * @param string $tagName
     * @return array
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function fetchItemsByTagFromBackend(string $tagName): array
    {
        if (\is_string($tagName)) {
            $driverResponse = $this->getItem($this->getTagKey($tagName));
            if ($driverResponse->isHit()) {
                $tagsItems = (array)$driverResponse->get();

                /**
                 * getItems() may provides expired item(s)
                 * themselves provided by a cache of item
                 * keys based stored the tag item.
                 * Therefore we pass a filter callback
                 * to remove the expired Item(s) provided by
                 * the item keys passed through getItems()
                 *
                 * #headache
                 */
                return \array_filter(
                    $this->getItems(\array_unique(\array_keys($tagsItems))),
                    static function (ExtendedCacheItemInterface $item) {
                        return $item->isHit();
                    }
                );
            }
            return [];
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string');
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getTagKey(string $key): string
    {
        return self::DRIVER_TAGS_KEY_PREFIX . $key;
    }

    /**
     * @inheritdoc
     */
    public function deleteItemsByTags(array $tagNames, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->deleteItemsByTag($tagName, $strategy);
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool)$return;
    }

    /**
     * @inheritdoc
     */
    public function deleteItemsByTag(string $tagName, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        if (\is_string($tagName)) {
            $return = null;
            foreach ($this->getItemsByTag($tagName, $strategy) as $item) {
                $result = $this->deleteItem($item->getKey());
                if ($return !== false) {
                    $return = $result;
                }
            }

            return (bool)$return;
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string');
    }

    /**
     * @inheritdoc
     */
    public function getItemsByTag(string $tagName, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): array
    {
        $items = $this->fetchItemsByTagFromBackend($tagName);
        if ($strategy === TaggableCacheItemPoolInterface::TAG_STRATEGY_ONLY) {
            foreach ($items as $key => $item) {
                if (\array_diff($item->getTags(), $tagName)) {
                    unset($items[$key]);
                }
            }
        }
        return $items;
    }

    /**
     * @inheritdoc
     */
    public function incrementItemsByTags(array $tagNames, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->incrementItemsByTag($tagName, $step, $strategy);
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool)$return;
    }

    /**
     * @inheritdoc
     */
    public function incrementItemsByTag(string $tagName, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        if (\is_string($tagName) && \is_int($step)) {
            foreach ($this->getItemsByTag($tagName, $strategy) as $item) {
                $item->increment($step);
                $this->saveDeferred($item);
            }

            return (bool)$this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string and $step an integer');
    }

    /**
     * @inheritdoc
     */
    public function decrementItemsByTags(array $tagNames, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->decrementItemsByTag($tagName, $step, $strategy);
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool)$return;
    }

    /**
     * @inheritdoc
     */
    public function decrementItemsByTag(string $tagName, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        if (\is_string($tagName) && \is_int($step)) {
            foreach ($this->getItemsByTag($tagName, $strategy) as $item) {
                $item->decrement($step);
                $this->saveDeferred($item);
            }

            return (bool)$this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string and $step an integer');
    }

    /**
     * @inheritdoc
     */
    public function appendItemsByTags(array $tagNames, $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->appendItemsByTag($tagName, $data, $strategy);
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool)$return;
    }

    /**
     * @inheritdoc
     */
    public function appendItemsByTag(string $tagName, $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        if (\is_string($tagName)) {
            foreach ($this->getItemsByTag($tagName, $strategy) as $item) {
                $item->append($data);
                $this->saveDeferred($item);
            }

            return (bool)$this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string');
    }

    /**
     * @inheritdoc
     */
    public function prependItemsByTags(array $tagNames, $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->prependItemsByTag($tagName, $data, $strategy);
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool)$return;
    }

    /**
     * @inheritdoc
     */
    public function prependItemsByTag(string $tagName, $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        if (\is_string($tagName)) {
            foreach ($this->getItemsByTag($tagName, $strategy) as $item) {
                $item->prepend($data);
                $this->saveDeferred($item);
            }

            return (bool)$this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string');
    }

    /**
     * @param array $wrapper
     * @return mixed
     */
    protected function driverUnwrapTags(array $wrapper)
    {
        return $wrapper[self::DRIVER_TAGS_WRAPPER_INDEX];
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function cleanItemTags(ExtendedCacheItemInterface $item)
    {
        $this->driverWriteTags($item->removeTags($item->getTags()));
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWriteTags(ExtendedCacheItemInterface $item): bool
    {
        /**
         * Do not attempt to write tags
         * on tags item, it can leads
         * to an infinite recursive calls
         */
        if (\strpos($item->getKey(), self::DRIVER_TAGS_KEY_PREFIX) === 0) {
            throw new PhpfastcacheLogicException('Trying to set tag(s) to an Tag item index: ' . $item->getKey());
        }

        if (!$item->getTags() && !$item->getRemovedTags()) {
            return true;
        }

        /**
         * @var $tagsItems ExtendedCacheItemInterface[]
         */
        $tagsItems = $this->getItems($this->getTagKeys($item->getTags()));

        foreach ($tagsItems as $tagsItem) {
            $data = $tagsItem->get();
            $expTimestamp = $item->getExpirationDate()->getTimestamp();

            /**
             * Using the key will
             * avoid to use array_unique
             * that has slow performances
             */

            $tagsItem->set(\array_merge((array)$data, [$item->getKey() => $expTimestamp]));

            /**
             * Set the expiration date
             * of the $tagsItem based
             * on the older $item
             * expiration date
             */
            if ($expTimestamp > $tagsItem->getExpirationDate()->getTimestamp()) {
                $tagsItem->expiresAt($item->getExpirationDate());
            }
            $this->driverWrite($tagsItem);
            $tagsItem->setHit(true);
        }

        /**
         * Also update removed tags to
         * keep the index up to date
         */
        $tagsItems = $this->getItems($this->getTagKeys($item->getRemovedTags()));

        foreach ($tagsItems as $tagsItem) {
            $data = (array)$tagsItem->get();

            unset($data[$item->getKey()]);
            $tagsItem->set($data);

            /**
             * Recalculate the expiration date
             *
             * If the $tagsItem does not have
             * any cache item references left
             * then remove it from tagsItems index
             */
            if (\count($data)) {
                $tagsItem->expiresAt((new DateTime())->setTimestamp(max($data)));
                $this->driverWrite($tagsItem);
                $tagsItem->setHit(true);
            } else {
                $this->deleteItem($tagsItem->getKey());
            }
        }

        return true;
    }

    /**
     * @param array $keys
     * @return array
     */
    protected function getTagKeys(array $keys): array
    {
        return \array_map(
            function (string $key) {
                return $this->getTagKey($key);
            },
            $keys
        );
    }
}
