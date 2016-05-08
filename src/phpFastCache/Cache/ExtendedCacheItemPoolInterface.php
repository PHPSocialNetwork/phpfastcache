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

namespace phpFastCache\Cache;

use phpFastCache\Entities\driverStatistic;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use \InvalidArgumentException;

/**
 * Interface ExtendedCacheItemPoolInterface
 * @package phpFastCache\Cache
 */
interface ExtendedCacheItemPoolInterface extends CacheItemPoolInterface
{
    /**
     * [phpFastCache Override]
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return ExtendedCacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem($key);

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    public function setItem(CacheItemInterface $item);

    /**
     * @return driverStatistic
     */
    public function getStats();

    /**
     * Returns a traversable set of cache items by a tag name.
     *
     * @param string $tagName
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItemsByTag($tagName);

    /**
     * Returns a traversable set of cache items by a tag name.
     *
     * @param array $tagNames
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItemsByTags(array $tagNames);

    /**
     * Removes the item from the pool by key.
     *
     * @param string $tagName
     *   The key for which to delete
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItemsByTag($tagName);

    /**
     * Removes the item from the pool by key.
     *
     * @param array $tagNames
     *   The key for which to delete
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItemsByTags(array $tagNames);
}