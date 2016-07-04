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

use InvalidArgumentException;
use Psr\Cache\CacheItemInterface;


trait ExtendedCacheItemPoolTrait
{
    use CacheItemPoolTrait;

    /**
     * Deletes all items in the pool.
     * @deprecated Use clear() instead
     * Will be removed in 5.1
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clean()
    {
        trigger_error('Cache clean() method is deprecated, use clear() method instead', E_USER_DEPRECATED);
        return $this->clear();
    }

    /**
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     * @param int $option json_encode() options
     * @param int $depth json_encode() depth
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getItemsAsJsonString(array $keys = [], $option = 0, $depth = 512)
    {
        $callback = function(CacheItemInterface $item){
            return $item->get();
        };
        return json_encode(array_map($callback, array_values($this->getItems($keys))), $option, $depth);
    }

    /**
     * @param string $tagName
     * @return \phpFastCache\Core\Item\ExtendedCacheItemInterface[]
     * @throws InvalidArgumentException
     */
    public function getItemsByTag($tagName)
    {
        if (is_string($tagName)) {
            $driverResponse = $this->getItem($this->getTagKey($tagName));
            if ($driverResponse->isHit()) {
                $items = (array) $driverResponse->get();

                return $this->getItems(array_unique(array_keys($items)));
            } else {
                return [];
            }
        } else {
            throw new InvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @param array $tagNames
     * @return \phpFastCache\Core\Item\ExtendedCacheItemInterface[]
     * @throws InvalidArgumentException
     */
    public function getItemsByTags(array $tagNames)
    {
        $items = [];
        foreach (array_unique($tagNames) as $tagName) {
            $items = array_merge($items, $this->getItemsByTag($tagName));
        }

        return $items;
    }

    /**
     * Returns A json string that represents an array of items by tags-based.
     *
     * @param array $tagNames
     * An indexed array of keys of items to retrieve.
     * @param int $option json_encode() options
     * @param int $depth json_encode() depth
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return string
     */
    public function getItemsByTagsAsJsonString(array $tagNames, $option = 0, $depth = 512)
    {
        $callback = function(CacheItemInterface $item){
            return $item->get();
        };

        return json_encode(array_map($callback, array_values($this->getItemsByTags($tagNames))), $option, $depth);
    }

    /**
     * @param string $tagName
     * @return bool|null
     * @throws InvalidArgumentException
     */
    public function deleteItemsByTag($tagName)
    {
        if (is_string($tagName)) {
            $return = null;
            foreach ($this->getItemsByTag($tagName) as $item) {
                $result = $this->driverDelete($item);
                if ($return !== false) {
                    $return = $result;
                }
            }

            return $return;
        } else {
            throw new InvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @param array $tagNames
     * @return bool|null
     * @throws InvalidArgumentException
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
    public function incrementItemsByTag($tagName, $step = 1)
    {
        if (is_string($tagName) && is_int($step)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->increment($step);
                $this->saveDeferred($item);
            }

            return $this->commit();
        } else {
            throw new InvalidArgumentException('$tagName must be a string and $step an integer');
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
    public function decrementItemsByTag($tagName, $step = 1)
    {
        if (is_string($tagName) && is_int($step)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->decrement($step);
                $this->saveDeferred($item);
            }

            return $this->commit();
        } else {
            throw new InvalidArgumentException('$tagName must be a string and $step an integer');
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
    public function appendItemsByTag($tagName, $data)
    {
        if (is_string($tagName)) {
            foreach ($this->getItemsByTag($tagName) as $item) {
                $item->append($data);
                $this->saveDeferred($item);
            }

            return $this->commit();
        } else {
            throw new InvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @inheritdoc
     */
    public function appendItemsByTags(array $tagNames, $data)
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->decrementItemsByTag($tagName, $data);
            if ($return !== false) {
                $return = $result;
            }
        }

        return $return;
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
            throw new InvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @inheritdoc
     */
    public function prependItemsByTags(array $tagNames, $data)
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->decrementItemsByTag($tagName, $data);
            if ($return !== false) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * Driver-related methods
     */

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return array [
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