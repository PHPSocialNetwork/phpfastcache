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

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Event\EventInterface;
use Phpfastcache\Exceptions\{
    PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException
};
use Psr\Cache\CacheItemInterface;

/**
 * Trait ExtendedCacheItemPoolTrait
 * @package Phpfastcache\Core\Pool
 * @method bool driverWriteTags(ExtendedCacheItemInterface $item) Imported from DriverBaseTrait
 */
trait ExtendedCacheItemPoolTrait
{
    use CacheItemPoolTrait, AbstractDriverPoolTrait;

    /**
     * @inheritdoc
     */
    public function getItemsAsJsonString(array $keys = [], $option = 0, $depth = 512): string
    {
        $callback = function (CacheItemInterface $item) {
            return $item->get();
        };
        return \json_encode(\array_map($callback, \array_values($this->getItems($keys))), $option, $depth);
    }

    /**
     * @inheritdoc
     */
    public function getItemsByTag($tagName): array
    {
        if (\is_string($tagName)) {
            $driverResponse = $this->getItem($this->getTagKey($tagName));
            if ($driverResponse->isHit()) {
                $items = (array)$driverResponse->get();

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
                return \array_filter($this->getItems(\array_unique(\array_keys($items))), function (ExtendedCacheItemInterface $item) {
                    return $item->isHit();
                });
            }
            return [];
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string');
    }

    /**
     * @inheritdoc
     */
    public function getItemsByTags(array $tagNames): array
    {
        $items = [];
        foreach (\array_unique($tagNames) as $tagName) {
            if (\is_string($tagName)) {
                $items = \array_merge($items, $this->getItemsByTag($tagName));
            } else {
                throw new PhpfastcacheInvalidArgumentException('$tagName must be a a string');
            }
        }

        return $items;
    }


    /**
     * @inheritdoc
     */
    public function getItemsByTagsAll(array $tagNames): array
    {
        $items = $this->getItemsByTags($tagNames);

        foreach ($items as $key => $item) {
            if (\array_diff($tagNames, $item->getTags())) {
                unset($items[$key]);
            }
        }

        return $items;
    }


    /**
     * @inheritdoc
     */
    public function getItemsByTagsAsJsonString(array $tagNames, $option = 0, $depth = 512): string
    {
        $callback = function (CacheItemInterface $item) {
            return $item->get();
        };

        return \json_encode(\array_map($callback, \array_values($this->getItemsByTags($tagNames))), $option, $depth);
    }

    /**
     * @inheritdoc
     */
    public function deleteItemsByTag($tagName): bool
    {
        if (\is_string($tagName)) {
            $return = null;
            foreach ($this->getItemsByTag($tagName) as $item) {
                $result = $this->deleteItem($item->getKey());
                if ($return !== false) {
                    $return = $result;
                }
            }

            return (bool) $return;
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string');
    }

    /**
     * @inheritdoc
     */
    public function deleteItemsByTags(array $tagNames): bool
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->deleteItemsByTag($tagName);
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool) $return;
    }

    /**
     * @inheritdoc
     */
    public function deleteItemsByTagsAll(array $tagNames): bool
    {
        $return = null;
        $items = $this->getItemsByTagsAll($tagNames);

        foreach ($items as $key => $item) {
            $result = $this->deleteItem($item->getKey());
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool) $return;
    }

    /**
     * @inheritdoc
     */
    public function incrementItemsByTag($tagName, $step = 1): bool
    {
        if (\is_string($tagName) && \is_int($step)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->increment($step);
                $this->saveDeferred($item);
            }

            return (bool) $this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string and $step an integer');
    }

    /**
     * @inheritdoc
     */
    public function incrementItemsByTags(array $tagNames, $step = 1): bool
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->incrementItemsByTag($tagName, $step);
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool) $return;
    }

    /**
     * @inheritdoc
     */
    public function incrementItemsByTagsAll(array $tagNames, $step = 1): bool
    {
        if (\is_int($step)) {
            $items = $this->getItemsByTagsAll($tagNames);

            foreach ($items as $key => $item) {
                $item->increment($step);
                $this->saveDeferred($item);
            }
            return (bool) $this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$step must be an integer');
    }

    /**
     * @inheritdoc
     */
    public function decrementItemsByTag($tagName, $step = 1): bool
    {
        if (\is_string($tagName) && \is_int($step)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->decrement($step);
                $this->saveDeferred($item);
            }

            return (bool) $this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string and $step an integer');
    }

    /**
     * @inheritdoc
     */
    public function decrementItemsByTags(array $tagNames, $step = 1): bool
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->decrementItemsByTag($tagName, $step);
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool) $return;
    }

    /**
     * @inheritdoc
     */
    public function decrementItemsByTagsAll(array $tagNames, $step = 1): bool
    {
        if (\is_int($step)) {
            $items = $this->getItemsByTagsAll($tagNames);

            foreach ($items as $key => $item) {
                $item->decrement($step);
                $this->saveDeferred($item);
            }
            return (bool) $this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$step must be an integer');
    }

    /**
     * @inheritdoc
     */
    public function appendItemsByTag($tagName, $data): bool
    {
        if (\is_string($tagName)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->append($data);
                $this->saveDeferred($item);
            }

            return (bool) $this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string');
    }

    /**
     * @inheritdoc
     */
    public function appendItemsByTags(array $tagNames, $data): bool
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->appendItemsByTag($tagName, $data);
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool) $return;
    }

    /**
     * @inheritdoc
     */
    public function appendItemsByTagsAll(array $tagNames, $data): bool
    {
        if (is_scalar($data)) {
            $items = $this->getItemsByTagsAll($tagNames);

            foreach ($items as $key => $item) {
                $item->append($data);
                $this->saveDeferred($item);
            }
            return (bool) $this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$data must be scalar');
    }

    /**
     * @inheritdoc
     */
    public function prependItemsByTag($tagName, $data): bool
    {
        if (\is_string($tagName)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->prepend($data);
                $this->saveDeferred($item);
            }

            return (bool) $this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$tagName must be a string');
    }

    /**
     * @inheritdoc
     */
    public function prependItemsByTags(array $tagNames, $data): bool
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->prependItemsByTag($tagName, $data);
            if ($return !== false) {
                $return = $result;
            }
        }

        return (bool) $return;
    }

    /**
     * @inheritdoc
     */
    public function prependItemsByTagsAll(array $tagNames, $data): bool
    {
        if (\is_scalar($data)) {
            $items = $this->getItemsByTagsAll($tagNames);

            foreach ($items as $key => $item) {
                $item->prepend($data);
                $this->saveDeferred($item);
            }
            return (bool) $this->commit();
        }

        throw new PhpfastcacheInvalidArgumentException('$data must be scalar');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return void
     */
    public function detachItem(CacheItemInterface $item)
    {
        if (isset($this->itemInstances[$item->getKey()])) {
            $this->deregisterItem($item->getKey());
        }
    }

    /**
     * @inheritdoc
     */
    public function detachAllItems()
    {
        foreach ($this->itemInstances as $item) {
            $this->detachItem($item);
        }
    }

    /**
     * @inheritdoc
     */
    public function attachItem(CacheItemInterface $item)
    {
        if (isset($this->itemInstances[$item->getKey()]) && \spl_object_hash($item) !== \spl_object_hash($this->itemInstances[$item->getKey()])) {
            throw new PhpfastcacheLogicException('The item already exists and cannot be overwritten because the Spl object hash mismatches ! You probably tried to re-attach a detached item which has been already retrieved from cache.');
        }

        $this->itemInstances[$item->getKey()] = $item;
    }


    /**
     * @internal This method de-register an item from $this->itemInstances
     * @param string $item
     */
    protected function deregisterItem(string $item)
    {
        unset($this->itemInstances[$item]);

        if (\gc_enabled()) {
            \gc_collect_cycles();
        }
    }

    /**
     * @param ExtendedCacheItemInterface $item
     */
    protected function cleanItemTags(ExtendedCacheItemInterface $item)
    {
        $this->driverWriteTags($item->removeTags($item->getTags()));
    }

    /**
     * Returns true if the item exists, is attached and the Spl Hash matches
     * Returns false if the item exists, is attached and the Spl Hash mismatches
     * Returns null if the item does not exists
     *
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool|null
     */
    public function isAttached(CacheItemInterface $item)
    {
        if (isset($this->itemInstances[$item->getKey()])) {
            return \spl_object_hash($item) === \spl_object_hash($this->itemInstances[$item->getKey()]);
        }
        return null;
    }

    /**
     * Set the EventManager instance
     *
     * @param EventInterface $em
     * @return ExtendedCacheItemPoolInterface
     */
    public function setEventManager(EventInterface $em): ExtendedCacheItemPoolInterface
    {
        $this->eventManager = $em;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function saveMultiple(...$items): bool
    {
        if (isset($items[0]) && \is_array($items[0])) {
            foreach ($items[0] as $item) {
                $this->save($item);
            }
            return true;
        }

        if (\is_array($items)) {
            foreach ($items as $item) {
                $this->save($item);
            }
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function isUsableInAutoContext(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return '';
    }
}