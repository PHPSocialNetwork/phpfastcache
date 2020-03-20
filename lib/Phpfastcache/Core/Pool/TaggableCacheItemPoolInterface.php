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

use InvalidArgumentException;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;


/**
 * Interface TaggableCacheItemPoolInterface
 * @package Phpfastcache\Core\Pool
 */
interface TaggableCacheItemPoolInterface
{
    public const DRIVER_TAGS_KEY_PREFIX = '_TAG_';

    public const DRIVER_TAGS_WRAPPER_INDEX = 'g';

    public const TAG_STRATEGY_ONE = 1;

    public const TAG_STRATEGY_ALL = 2;

    public const TAG_STRATEGY_ONLY = 4;

    /**
     * Returns a traversable set of cache items by a tag name.
     *
     * @param string $tagName
     * An indexed array of keys of items to retrieve.
     *
     * @param int $strategy
     *
     * @return ExtendedCacheItemInterface[]
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function getItemsByTag(string $tagName, int $strategy = self::TAG_STRATEGY_ONE): array;

    /**
     * Returns a traversable set of cache items by one of multiple tag names.
     *
     * @param string[] $tagNames
     * An indexed array of keys of items to retrieve.
     *
     * @param int $strategy
     *
     * @return ExtendedCacheItemInterface[]
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function getItemsByTags(array $tagNames, int $strategy = self::TAG_STRATEGY_ONE): array;

    /**
     * Returns A json string that represents an array of items by tags-based.
     *
     * @param string[] $tagNames
     * An indexed array of keys of items to retrieve.
     * @param int $option \json_encode() options
     * @param int $depth \json_encode() depth
     * @param int $strategy
     *
     * @return string
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function getItemsByTagsAsJsonString(array $tagNames, int $option = 0, int $depth = 512, int $strategy = self::TAG_STRATEGY_ONE): string;

    /**
     * Removes the item from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to delete
     *
     * @param int $strategy
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function deleteItemsByTag(string $tagName, int $strategy = self::TAG_STRATEGY_ONE): bool;

    /**
     * Removes the item from the pool by one of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to delete
     *
     * @param int $strategy
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function deleteItemsByTags(array $tagNames, int $strategy = self::TAG_STRATEGY_ONE): bool;

    /**
     * Increment the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to increment
     *
     * @param int $step
     *
     * @param int $strategy
     *
     * @return bool
     *   True if the item was successfully incremented. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function incrementItemsByTag(string $tagName, int $step = 1, int $strategy = self::TAG_STRATEGY_ONE): bool;

    /**
     * Increment the items from the pool by one of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to increment
     *
     * @param int $step
     *
     * @param int $strategy
     *
     * @return bool
     *   True if the items were successfully incremented. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function incrementItemsByTags(array $tagNames, int $step = 1, int $strategy = self::TAG_STRATEGY_ONE): bool;

    /**
     * Decrement the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to decrement
     *
     * @param int $step
     *
     * @param int $strategy
     *
     * @return bool
     *   True if the item was successfully decremented. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function decrementItemsByTag(string $tagName, int $step = 1, int $strategy = self::TAG_STRATEGY_ONE): bool;

    /**
     * Decrement the items from the pool by one of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to decrement
     *
     * @param int $step
     *
     * @param int $strategy
     *
     * @return bool
     *   True if the item was successfully decremented. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function decrementItemsByTags(array $tagNames, int $step = 1, int $strategy = self::TAG_STRATEGY_ONE): bool;

    /**
     * Decrement the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to append
     *
     * @param array|string $data
     *
     * @param int $strategy
     *
     * @return bool
     *   True if the item was successfully appended. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function appendItemsByTag(string $tagName, $data, int $strategy = self::TAG_STRATEGY_ONE): bool;

    /**
     * Append the items from the pool by one of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to append
     *
     * @param array|string $data
     *
     * @param int $strategy
     *
     * @return bool
     *   True if the items were successfully appended. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function appendItemsByTags(array $tagNames, $data, int $strategy = self::TAG_STRATEGY_ONE): bool;

    /**
     * Prepend the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to prepend
     *
     * @param array|string $data
     *
     * @param int $strategy
     *
     * @return bool
     *   True if the item was successfully prepended. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function prependItemsByTag(string $tagName, $data, int $strategy = self::TAG_STRATEGY_ONE): bool;

    /**
     * Prepend the items from the pool by one of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to prepend
     *
     * @param array|string $data
     *
     * @param int $strategy
     *
     * @return bool
     *   True if the item was successfully prepended. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function prependItemsByTags(array $tagNames, $data, int $strategy = self::TAG_STRATEGY_ONE): bool;
}
