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

use InvalidArgumentException;
use phpFastCache\Entities\driverStatistic;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Interface ExtendedCacheItemPoolInterface
 * @package phpFastCache\Cache
 */
interface ExtendedCacheItemPoolInterface extends CacheItemPoolInterface
{
    /**
     * @return array
     */
    public function getConfig();

    /**
     * @return string
     */
    public function getDriverName();


    /**
     * [phpFastCache phpDoc Override]
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
     * [phpFastCache phpDoc Override]
     * Returns a traversable set of cache items.
     *
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return ExtendedCacheItemInterface[]
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = []);

    /**
     * Returns A json string that represents an array of items.
     *
     * @param array $keys
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
    public function getItemsAsJsonString(array $keys = [], $option = 0, $depth = 512);

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    public function setItem(CacheItemInterface $item);

    /**
     * Deletes all items in the pool.
     * @deprecated Use clear() instead
     * Will be removed in 5.1
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clean();

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
    public function getItemsByTagsAsJsonString(array $tagNames, $option = 0, $depth = 512);

    /**
     * Removes the item from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to delete
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
     * Removes the item from the pool by tag.
     *
     * @param array $tagNames
     *   The tag for which to delete
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItemsByTags(array $tagNames);

    /**
     * Increment the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to increment
     * @param int $step
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully incremented. False if there was an error.
     */
    public function incrementItemsByTag($tagName, $step = 1);

    /**
     * Increment the items from the pool by tag.
     *
     * @param array $tagNames
     *   The tag for which to increment
     * @param int $step
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully incremented. False if there was an error.
     */
    public function incrementItemsByTags(array $tagNames, $step = 1);


    /**
     * Decrement the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to decrement
     * @param int $step
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully decremented. False if there was an error.
     */
    public function decrementItemsByTag($tagName, $step = 1);

    /**
     * Decrement the items from the pool by tag.
     *
     * @param array $tagNames
     *   The tag for which to decrement
     * @param int $step
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully decremented. False if there was an error.
     */
    public function decrementItemsByTags(array $tagNames, $step = 1);

    /**
     * Decrement the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to append
     *
     * @param array|string $data
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully appended. False if there was an error.
     */
    public function appendItemsByTag($tagName, $data);

    /**
     * Decrement the items from the pool by tag.
     *
     * @param array $tagNames
     *   The tag for which to append
     *
     * @param array|string $data
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully appended. False if there was an error.
     */
    public function appendItemsByTags(array $tagNames, $data);

    /**
     * Prepend the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to prepend
     *
     * @param array|string $data
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully prepended. False if there was an error.
     */
    public function prependItemsByTag($tagName, $data);

    /**
     * Prepend the items from the pool by tag.
     *
     * @param array $tagNames
     *   The tag for which to prepend
     *
     * @param array|string $data
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully prepended. False if there was an error.
     */
    public function prependItemsByTags(array $tagNames, $data);

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return void
     */
    public function detachItem(CacheItemInterface $item);

    /**
     * @return void
     */
    public function detachAllItems();

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return void
     * @throws \LogicException
     */
    public function attachItem(CacheItemInterface $item);

    /**
     * Returns true if the item exists, is attached and the Spl Hash matches
     * Returns false if the item exists, is attached and the Spl Hash mismatches
     * Returns null if the item does not exists
     *
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool|null
     * @throws \LogicException
     */
    public function isAttached(CacheItemInterface $item);
}