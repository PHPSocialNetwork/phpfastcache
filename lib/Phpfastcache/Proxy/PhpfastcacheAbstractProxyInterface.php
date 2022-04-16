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

namespace Phpfastcache\Proxy;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolInterface;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Entities\DriverStatistic;
use Psr\Cache\CacheItemInterface;

/**
 * @method ExtendedCacheItemInterface getItem(string $key) Retrieve an item and returns an empty item if not found
 * @method ExtendedCacheItemInterface[] getItems(string[] $keys) Retrieve an item and returns an empty item if not found
 * @method bool hasItem(string $key) Tests if an item exists
 * @method static string getConfigClass()
 * @method ConfigurationOption getItemClass()
 * @method ConfigurationOption getConfig()
 * @method ConfigurationOption getDefaultConfig()
 * @method string getDriverName()
 * @method string getInstanceId()
 * @method bool deleteItem(string $key) Delete an item
 * @method bool deleteItems(string[] $keys) Delete some items
 * @method bool save(CacheItemInterface $item) Save an item
 * @method bool saveMultiple(CacheItemInterface ...$items) Save multiple items
 * @method bool saveDeferred(CacheItemInterface $item) Sets a cache item to be persisted later
 * @method bool commit() Persists any deferred cache items
 * @method bool clear() Allow you to completely empty the cache and restart from the beginning
 * @method DriverStatistic getStats() Returns a DriverStatistic object
 * @method string getHelp() Returns help about a driver, if available
 * @method ExtendedCacheItemInterface getItemsByTag(string $tagName, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) Return items by a tag
 * @method ExtendedCacheItemInterface[] getItemsByTags(string[] $tagNames, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) Return items by some tags
 * @method bool deleteItemsByTag(string $tagName, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) Delete items by a tag
 * @method bool deleteItemsByTags(string[] $tagNames, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) // Delete items by some tags
 * @method void incrementItemsByTag(string $tagName, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) // Increment items by a tag
 * @method void incrementItemsByTags(string[] $tagNames, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) // Increment items by some tags
 * @method void decrementItemsByTag(string $tagName, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) // Decrement items by a tag
 * @method void decrementItemsByTags(string[] $tagNames, int $step = 1, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) // Decrement items by some tags
 * @method void appendItemsByTag(string $tagName, mixed $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) // Append items by a tag
 * @method void appendItemsByTags(string[] $tagNames, mixed $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) // Append items by a tags
 * @method void prependItemsByTag(string $tagName, mixed $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) // Prepend items by a tag
 * @method void prependItemsByTags(string[] $tagNames, mixed $data, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE) // Prepend items by a tags
 * @method string getItemsAsJsonString(string[] $tagNames, int $option, int $depth, int $strategy = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE)
 * @method ExtendedCacheItemInterface detachItem(CacheItemInterface $item)
 * @method ExtendedCacheItemInterface attachItem(CacheItemInterface $item)
 * @method ExtendedCacheItemInterface detachAllItems()
 * @method bool isAttached(CacheItemInterface $item)
 * @method DriverIO getIO()
 */
interface PhpfastcacheAbstractProxyInterface
{
    public const DRIVER_CHECK_FAILURE = ExtendedCacheItemPoolInterface::DRIVER_CHECK_FAILURE;
    public const DRIVER_CONNECT_FAILURE = ExtendedCacheItemPoolInterface::DRIVER_CONNECT_FAILURE;
    public const DRIVER_KEY_WRAPPER_INDEX = ExtendedCacheItemPoolInterface::DRIVER_KEY_WRAPPER_INDEX;
    public const DRIVER_DATA_WRAPPER_INDEX = ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX;
    public const DRIVER_EDATE_WRAPPER_INDEX = ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX;
    public const DRIVER_CDATE_WRAPPER_INDEX = ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX;
    public const DRIVER_MDATE_WRAPPER_INDEX = ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX;

    public const DRIVER_TAGS_KEY_PREFIX = TaggableCacheItemPoolInterface::DRIVER_TAGS_KEY_PREFIX;
    public const DRIVER_TAGS_WRAPPER_INDEX = TaggableCacheItemPoolInterface::DRIVER_TAGS_WRAPPER_INDEX;
    public const TAG_STRATEGY_ONE = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE;
    public const TAG_STRATEGY_ALL = TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL;
    public const TAG_STRATEGY_ONLY = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONLY;

    /**
     * @param string $name
     * @param array<mixed> $args
     * @return mixed
     */
    public function __call(string $name, array $args): mixed;
}
