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
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Psr\Cache\CacheItemInterface;

/**
 * Trait TaggableCacheItemPoolTrait
 * @package Phpfastcache\Core\Pool
 */
trait TaggableCacheItemPoolTrait
{
    use ExtendedCacheItemPoolTrait;

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function getItemsByTagsAsJsonString(
        array $tagNames,
        int $option = \JSON_THROW_ON_ERROR,
        int $depth = 512,
        int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE
    ): string {
        return \json_encode(
            \array_map(
                static fn(CacheItemInterface $item) => $item->get(),
                \array_values($this->getItemsByTags($tagNames, $strategy))
            ),
            $option,
            $depth,
        );
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function getItemsByTags(array $tagNames, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): array
    {
        $items = [];
        foreach (\array_unique($tagNames) as $tagName) {
            $items[] = $this->fetchItemsByTagFromBackend($tagName);
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
     * @return ExtendedCacheItemInterface[]
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function fetchItemsByTagFromBackend(string $tagName): array
    {
        $driverResponse = $this->getItem($this->getTagKey($tagName));
        if ($driverResponse->isHit()) {
            $tagsItems = (array)$driverResponse->get();

            /**
             * getItems() may provide expired item(s)
             * themselves provided by a cache of item
             * keys based stored the tag item.
             * Therefore, we pass a filter callback
             * to remove the expired Item(s) provided by
             * the item keys passed through getItems()
             *
             * #headache
             */
            return \array_filter(
                $this->getItems(\array_unique(\array_keys($tagsItems))),
                static fn (ExtendedCacheItemInterface $item) => $item->isHit(),
            );
        }
        return [];
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getTagKey(string $key): string
    {
        return TaggableCacheItemPoolInterface::DRIVER_TAGS_KEY_PREFIX . $key;
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     */
    public function deleteItemsByTags(array $tagNames, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = true;

        foreach ($this->getItemsByTags($tagNames, $strategy) as $item) {
            $result = $this->deleteItem($item->getKey());
            if ($result !== true) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     */
    public function deleteItemsByTag(string $tagName, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = true;
        foreach ($this->getItemsByTag($tagName, $strategy) as $item) {
            $result = $this->deleteItem($item->getKey());
            if ($result !== true) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function getItemsByTag(string $tagName, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): array
    {
        $items = $this->fetchItemsByTagFromBackend($tagName);
        if ($strategy === TaggableCacheItemPoolInterface::TAG_STRATEGY_ONLY) {
            foreach ($items as $key => $item) {
                if (\array_diff($item->getTags(), [$tagName])) {
                    unset($items[$key]);
                }
            }
        }
        return $items;
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function incrementItemsByTags(array $tagNames, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = true;
        foreach ($tagNames as $tagName) {
            $result = $this->incrementItemsByTag($tagName, $step, $strategy);
            if ($result !== true) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function incrementItemsByTag(string $tagName, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        foreach ($this->getItemsByTag($tagName, $strategy) as $item) {
            $item->increment($step);
            $this->saveDeferred($item);
        }

        return $this->commit();
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function decrementItemsByTags(array $tagNames, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = true;
        foreach ($tagNames as $tagName) {
            $result = $this->decrementItemsByTag($tagName, $step, $strategy);
            if ($result !== true) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function decrementItemsByTag(string $tagName, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        foreach ($this->getItemsByTag($tagName, $strategy) as $item) {
            $item->decrement($step);
            $this->saveDeferred($item);
        }

        return $this->commit();
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function appendItemsByTags(array $tagNames, array|string $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = true;
        foreach ($tagNames as $tagName) {
            $result = $this->appendItemsByTag($tagName, $data, $strategy);
            if ($result !== true) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function appendItemsByTag(string $tagName, array|string $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        foreach ($this->getItemsByTag($tagName, $strategy) as $item) {
            $item->append($data);
            $this->saveDeferred($item);
        }

        return $this->commit();
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function prependItemsByTags(array $tagNames, array|string $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        $return = true;
        foreach ($tagNames as $tagName) {
            $result = $this->prependItemsByTag($tagName, $data, $strategy);
            if ($result !== true) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function prependItemsByTag(string $tagName, array|string $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE): bool
    {
        foreach ($this->getItemsByTag($tagName, $strategy) as $item) {
            $item->prepend($data);
            $this->saveDeferred($item);
        }

        return $this->commit();
    }

    /**
     * @param array<mixed> $wrapper
     * @return string[]
     */
    protected function driverUnwrapTags(array $wrapper): array
    {
        return $wrapper[TaggableCacheItemPoolInterface::DRIVER_TAGS_WRAPPER_INDEX];
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function cleanItemTags(ExtendedCacheItemInterface $item): void
    {
        $this->driverWriteTags($item->removeTags($item->getTags()));
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     */
    protected function driverWriteTags(ExtendedCacheItemInterface $item): bool
    {
        /**
         * Do not attempt to write tags
         * on tags item, it can lead
         * to an infinite recursive calls
         */
        if (str_starts_with($item->getKey(), TaggableCacheItemPoolInterface::DRIVER_TAGS_KEY_PREFIX)) {
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

            $tagsItem->set(\array_merge((array)$data, [$item->getKey() => $expTimestamp]))
                ->expiresAt($item->getExpirationDate());

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
     * @param array<string> $keys
     * @return array<string>
     */
    protected function getTagKeys(array $keys): array
    {
        return \array_map(
            fn (string $key) => $this->getTagKey($key),
            $keys
        );
    }
}
