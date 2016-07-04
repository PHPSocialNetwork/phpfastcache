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
namespace phpFastCache\Proxy;

use phpFastCache\Cache\ExtendedCacheItemInterface;
use phpFastCache\CacheManager;
use phpFastCache\Entities\driverStatistic;
use Psr\Cache\CacheItemInterface;

/**
 * Class phpFastCache
 *
 * Handle methods using annotations for IDE
 * because they're handled by __call()
 * Check out ExtendedCacheItemInterface to see all
 * the drivers methods magically implemented
 *
 * @method ExtendedCacheItemInterface getItem($key) Retrieve an item and returns an empty item if not found
 * @method ExtendedCacheItemInterface[] getItems(array $keys) Retrieve an item and returns an empty item if not found
 * @method bool hasItem() hasItem($key) Tests if an item exists
 * @method bool deleteItem(string $key) Delete an item
 * @method bool deleteItems(array $keys) Delete some items
 * @method bool save(CacheItemInterface $item) Save an item
 * @method bool saveDeferred(CacheItemInterface $item) Sets a cache item to be persisted later
 * @method bool commit() Persists any deferred cache items
 * @method bool clear() Allow you to completely empty the cache and restart from the beginning
 * @method driverStatistic stats() Returns a driverStatistic object
 * @method ExtendedCacheItemInterface getItemsByTag($tagName) Return items by a tag
 * @method ExtendedCacheItemInterface[] getItemsByTags(array $tagNames) Return items by some tags
 * @method bool deleteItemsByTag($tagName) Delete items by a tag
 * @method bool deleteItemsByTags(array $tagNames) // Delete items by some tags
 * @method void incrementItemsByTag($tagName, $step = 1) // Increment items by a tag
 * @method void incrementItemsByTags(array $tagNames, $step = 1) // Increment items by some tags
 * @method void decrementItemsByTag($tagName, $step = 1) // Decrement items by a tag
 * @method void decrementItemsByTags(array $tagNames, $step = 1) // Decrement items by some tags
 * @method void appendItemsByTag($tagName, $data) // Append items by a tag
 * @method void appendItemsByTags(array $tagNames, $data) // Append items by a tags
 * @method void prependItemsByTag($tagName, $data) // Prepend items by a tag
 * @method void prependItemsByTags(array $tagNames, $data) // Prepend items by a tags
 */
abstract class phpFastCacheAbstractProxy
{
    /**
     * @var \phpFastCache\Cache\ExtendedCacheItemPoolInterface
     */
    protected $instance;

    /**
     * phpFastCache constructor.
     * @param string $driver
     * @param array $config
     */
    public function __construct($driver = 'auto', array $config = [])
    {
        $this->instance = CacheManager::getInstance($driver, $config);
    }

    /**
     * @param $name
     * @param $args
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($name, $args)
    {
        if(method_exists($this->instance, $name)){
            return call_user_func_array([$this->instance, $name], $args);
        }else{
            throw new \BadMethodCallException(sprintf('Method %s does not exists', $name));
        }
    }
}
