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

use BadMethodCallException;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Psr\Cache\CacheItemInterface;

/**
 * Class phpFastCache
 *
 * Handle methods using annotations for IDE
 * because they're handled by __call()
 * Check out ExtendedCacheItemInterface to see all
 * the drivers methods magically implemented
 *
 * @method ExtendedCacheItemInterface getItem(string $key) Retrieve an item and returns an empty item if not found
 * @method ExtendedCacheItemInterface[] getItems(string[] $keys) Retrieve an item and returns an empty item if not found
 * @method bool hasItem(string $key) Tests if an item exists
 * @method string getConfigClass()
 * @method ConfigurationOption getConfig()
 * @method ConfigurationOption getDefaultConfig()
 * @method string getDriverName()
 * @method string getInstanceId()
 * @method bool deleteItem(string $key) Delete an item
 * @method bool deleteItems(array $keys) Delete some items
 * @method bool save(CacheItemInterface $item) Save an item
 * @method bool saveMultiple(CacheItemInterface ...$items) Save multiple items
 * @method bool saveDeferred(CacheItemInterface $item) Sets a cache item to be persisted later
 * @method bool commit() Persists any deferred cache items
 * @method bool clear() Allow you to completely empty the cache and restart from the beginning
 * @method DriverStatistic getStats() Returns a DriverStatistic object
 * @method string getHelp() Returns help about a driver, if available
 * @method ExtendedCacheItemInterface getItemsByTag(string $tagName) Return items by a tag
 * @method ExtendedCacheItemInterface[] getItemsByTags(array $tagNames) Return items by some tags
 * @method bool deleteItemsByTag(string $tagName) Delete items by a tag
 * @method bool deleteItemsByTags(array $tagNames) // Delete items by some tags
 * @method void incrementItemsByTag(string $tagName, int $step = 1) // Increment items by a tag
 * @method void incrementItemsByTags(array $tagNames, int $step = 1) // Increment items by some tags
 * @method void decrementItemsByTag(string $tagName, int $step = 1) // Decrement items by a tag
 * @method void decrementItemsByTags(array $tagNames, int $step = 1) // Decrement items by some tags
 * @method void appendItemsByTag(string $tagName, mixed $data) // Append items by a tag
 * @method void appendItemsByTags(array $tagNames, mixed $data) // Append items by a tags
 * @method void prependItemsByTag(string $tagName, mixed $data) // Prepend items by a tag
 * @method void prependItemsByTags(array $tagNames, mixed $data) // Prepend items by a tags
 * @method string getItemsAsJsonString(array $keys, int $options, int$depth)
 * @method ExtendedCacheItemInterface detachItem(CacheItemInterface $item)
 * @method ExtendedCacheItemInterface attachItem(CacheItemInterface $item)
 * @method ExtendedCacheItemInterface detachAllItems()
 * @method bool isAttached()
 * @method DriverIO getIO()
 */
abstract class PhpfastcacheAbstractProxy
{
    /**
     * @var ExtendedCacheItemPoolInterface
     */
    protected ExtendedCacheItemPoolInterface $instance;

    /**
     * PhpfastcacheAbstractProxy constructor.
     * @param string $driver
     * @param null $config
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheLogicException
     */
    public function __construct(string $driver, $config = null)
    {
        $this->instance = CacheManager::getInstance($driver, $config);
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $args)
    {
        if (\method_exists($this->instance, $name)) {
            return $this->instance->$name(...$args);
        }

        throw new BadMethodCallException(\sprintf('Method %s does not exists', $name));
    }
}
