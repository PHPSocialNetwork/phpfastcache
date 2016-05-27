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

namespace phpFastCache\Core;

use InvalidArgumentException;

trait ExtendedCacheItemPoolTrait
{
    /**
     * @param string $tagName
     * @return \phpFastCache\Cache\ExtendedCacheItemInterface[]
     * @throws InvalidArgumentException
     */
    public function getItemsByTag($tagName)
    {
        if (is_string($tagName)) {
            $driverResponse = $this->driverRead($this->getTagKey($tagName));
            if ($driverResponse) {
                $items = (array) $this->driverUnwrapData($driverResponse);

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
     * @return \phpFastCache\Cache\ExtendedCacheItemInterface[]
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
}