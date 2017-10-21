<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace phpFastCache\Core\Pool;

use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\EventManager;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheLogicException;
use Psr\Cache\CacheItemInterface;


trait ExtendedCacheItemPoolTrait
{
    use CacheItemPoolTrait;

    /**
     * @inheritdoc
     */
    public function getItemsAsJsonString(array $keys = [], $option = 0, $depth = 512)
    {
        $callback = function (CacheItemInterface $item) {
            return $item->get();
        };
        return json_encode(array_map($callback, array_values($this->getItems($keys))), $option, $depth);
    }

    /**
     * @inheritdoc
     */
    public function getItemsByTag($tagName)
    {
        if (is_string($tagName)) {
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
                return array_filter($this->getItems(array_unique(array_keys($items))), function (ExtendedCacheItemInterface $item) {
                    return $item->isHit();
                });
            } else {
                return [];
            }
        } else {
            throw new phpFastCacheInvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @inheritdoc
     */
    public function getItemsByTags(array $tagNames)
    {
        $items = [];
        foreach (array_unique($tagNames) as $tagName) {
            if (is_string($tagName)) {
                $items = array_merge($items, $this->getItemsByTag($tagName));
            } else {
                throw new phpFastCacheInvalidArgumentException('$tagName must be a a string');
            }
        }

        return $items;
    }


    /**
     * @inheritdoc
     */
    public function getItemsByTagsAll(array $tagNames)
    {
        $items = $this->getItemsByTags($tagNames);

        foreach ($items as $key => $item) {
            if (array_diff($tagNames, $item->getTags())) {
                unset($items[ $key ]);
            }
        }

        return $items;
    }


    /**
     * @inheritdoc
     */
    public function getItemsByTagsAsJsonString(array $tagNames, $option = 0, $depth = 512)
    {
        $callback = function (CacheItemInterface $item) {
            return $item->get();
        };

        return json_encode(array_map($callback, array_values($this->getItemsByTags($tagNames))), $option, $depth);
    }

    /**
     * @inheritdoc
     */
    public function deleteItemsByTag($tagName)
    {
        if (is_string($tagName)) {
            $return = null;
            foreach ($this->getItemsByTag($tagName) as $item) {
                $result = $this->deleteItem($item->getKey());
                if ($return !== false) {
                    $return = $result;
                }
            }

            return $return;
        } else {
            throw new phpFastCacheInvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteItemsByTags(array $tagNames)
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->deleteItemsByTag($tagName);
            if ($return !== false) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function deleteItemsByTagsAll(array $tagNames)
    {
        $return = null;
        $items = $this->getItemsByTagsAll($tagNames);

        foreach ($items as $key => $item) {
            $result = $this->deleteItem($item->getKey());
            if ($return !== false) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function incrementItemsByTag($tagName, $step = 1)
    {
        if (is_string($tagName) && is_int($step)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->increment($step);
                $this->saveDeferred($item);
            }

            return $this->commit();
        } else {
            throw new phpFastCacheInvalidArgumentException('$tagName must be a string and $step an integer');
        }
    }

    /**
     * @inheritdoc
     */
    public function incrementItemsByTags(array $tagNames, $step = 1)
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->incrementItemsByTag($tagName, $step);
            if ($return !== false) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function incrementItemsByTagsAll(array $tagNames, $step = 1)
    {
        if (is_int($step)) {
            $items = $this->getItemsByTagsAll($tagNames);

            foreach ($items as $key => $item) {
                $item->increment($step);
                $this->saveDeferred($item);
            }
            return $this->commit();
        } else {
            throw new phpFastCacheInvalidArgumentException('$step must be an integer');
        }
    }

    /**
     * @inheritdoc
     */
    public function decrementItemsByTag($tagName, $step = 1)
    {
        if (is_string($tagName) && is_int($step)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->decrement($step);
                $this->saveDeferred($item);
            }

            return $this->commit();
        } else {
            throw new phpFastCacheInvalidArgumentException('$tagName must be a string and $step an integer');
        }
    }

    /**
     * @inheritdoc
     */
    public function decrementItemsByTags(array $tagNames, $step = 1)
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->decrementItemsByTag($tagName, $step);
            if ($return !== false) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function decrementItemsByTagsAll(array $tagNames, $step = 1)
    {
        if (is_int($step)) {
            $items = $this->getItemsByTagsAll($tagNames);

            foreach ($items as $key => $item) {
                $item->decrement($step);
                $this->saveDeferred($item);
            }
            return $this->commit();
        } else {
            throw new phpFastCacheInvalidArgumentException('$step must be an integer');
        }
    }

    /**
     * @inheritdoc
     */
    public function appendItemsByTag($tagName, $data)
    {
        if (is_string($tagName)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->append($data);
                $this->saveDeferred($item);
            }

            return $this->commit();
        } else {
            throw new phpFastCacheInvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @inheritdoc
     */
    public function appendItemsByTags(array $tagNames, $data)
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->appendItemsByTag($tagName, $data);
            if ($return !== false) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function appendItemsByTagsAll(array $tagNames, $data)
    {
        if (is_scalar($data)) {
            $items = $this->getItemsByTagsAll($tagNames);

            foreach ($items as $key => $item) {
                $item->append($data);
                $this->saveDeferred($item);
            }
            return $this->commit();
        } else {
            throw new phpFastCacheInvalidArgumentException('$data must be scalar');
        }
    }

    /**
     * @inheritdoc
     */
    public function prependItemsByTag($tagName, $data)
    {
        if (is_string($tagName)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->prepend($data);
                $this->saveDeferred($item);
            }

            return $this->commit();
        } else {
            throw new phpFastCacheInvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @inheritdoc
     */
    public function prependItemsByTags(array $tagNames, $data)
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->prependItemsByTag($tagName, $data);
            if ($return !== false) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function prependItemsByTagsAll(array $tagNames, $data)
    {
        if (is_scalar($data)) {
            $items = $this->getItemsByTagsAll($tagNames);

            foreach ($items as $key => $item) {
                $item->prepend($data);
                $this->saveDeferred($item);
            }
            return $this->commit();
        } else {
            throw new phpFastCacheInvalidArgumentException('$data must be scalar');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return void
     */
    public function detachItem(CacheItemInterface $item)
    {
        if (isset($this->itemInstances[ $item->getKey() ])) {
            $this->deregisterItem($item);
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
        if (isset($this->itemInstances[ $item->getKey() ]) && spl_object_hash($item) !== spl_object_hash($this->itemInstances[ $item->getKey() ])) {
            throw new phpFastCacheLogicException('The item already exists and cannot be overwritten because the Spl object hash mismatches ! You probably tried to re-attach a detached item which has been already retrieved from cache.');
        } else {
            $this->itemInstances[ $item->getKey() ] = $item;
        }
    }


    /**
     * @internal This method de-register an item from $this->itemInstances
     * @param CacheItemInterface|string $item
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function deregisterItem($item)
    {
        if ($item instanceof CacheItemInterface) {
            unset($this->itemInstances[ $item->getKey() ]);

        } else if (is_string($item)) {
            unset($this->itemInstances[ $item ]);
        } else {
            throw new phpFastCacheInvalidArgumentException('Invalid type for $item variable');
        }
        if (gc_enabled()) {
            gc_collect_cycles();
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
        if (isset($this->itemInstances[ $item->getKey() ])) {
            return spl_object_hash($item) === spl_object_hash($this->itemInstances[ $item->getKey() ]);
        }
        return null;
    }

    /**
     * Set the EventManager instance
     *
     * @param EventManager $em
     */
    public function setEventManager(EventManager $em)
    {
        $this->eventManager = $em;
    }

    /**
     * @inheritdoc
     */
    public function saveMultiple(...$items)
    {
        if (isset($items[ 0 ]) && is_array($items[ 0 ])) {
            foreach ($items[ 0 ] as $item) {
                $this->save($item);
            }
            return true;
        } else if (is_array($items)) {
            foreach ($items as $item) {
                $this->save($item);
            }
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return '';
    }

    /**
     * Driver-related methods
     */

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array [
     *      'd' => 'THE ITEM DATA'
     *      't' => 'THE ITEM DATE EXPIRATION'
     *      'g' => 'THE ITEM TAGS'
     * ]
     *
     */
    abstract protected function driverRead(CacheItemInterface $item);

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    abstract protected function driverWrite(CacheItemInterface $item);

    /**
     * @return bool
     */
    abstract protected function driverClear();

    /**
     * @return bool
     */
    abstract protected function driverConnect();

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     */
    abstract protected function driverDelete(CacheItemInterface $item);
}