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

namespace phpFastCache;

use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;

/**
 * Class CacheManager
 * @package phpFastCache
 *
 * == ItemPool Events ==
 * @method Void onCacheGetItem() onCacheGetItem(Callable $callable)
 * @method Void onCacheDeleteItem() onCacheDeleteItem(Callable $callable)
 * @method Void onCacheSaveItem() onCacheSaveItem(Callable $callable)
 * @method Void onCacheSaveDeferredItem() onCacheSaveDeferredItem(Callable $callable)
 * @method Void onCacheCommitItem() onCacheCommitItem(Callable $callable)
 * @method Void onCacheClearItem() onCacheClearItem(Callable $callable)
 * @method Void onCacheWriteFileOnDisk() onCacheWriteFileOnDisk(Callable $callable)
 * @method Void onCacheGetItemInSlamBatch() onCacheGetItemInSlamBatch(Callable $callable)
 *
 * == Item Events ==
 * @method Void onCacheItemSet() onCacheItemSet(Callable $callable)
 * @method Void onCacheItemExpireAt() onCacheItemExpireAt(Callable $callable)
 * @method Void onCacheItemExpireAfter() onCacheItemExpireAfter(Callable $callable)
 *
 *
 */
class EventManager
{
    /**
     * @var $this
     */
    protected static $instance;

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @return \phpFastCache\EventManager
     */
    public static function getInstance()
    {
        return (self::$instance ?: self::$instance = new self);
    }

    /**
     * EventManager constructor.
     */
    protected function __construct()
    {
    }

    /**
     * @param string $eventName
     * @param array ...$args
     */
    public function dispatch($eventName, ...$args)
    {
        /**
         * Replace array_key_exists by isset
         * due to performance issue on huge
         * loop dispatching operations
         */
        if (isset($this->events[ $eventName ])) {
            foreach ($this->events[ $eventName ] as $event) {
                call_user_func_array($event, $args);
            }
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @throws phpFastCacheInvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'on') === 0) {
            $name = substr($name, 2);
            if (is_callable($arguments[ 0 ])) {
                if (isset($arguments[ 1 ]) && is_string($arguments[ 0 ])) {
                    $this->events[ $name ][ $arguments[ 1 ] ] = $arguments[ 0 ];
                } else {
                    $this->events[ $name ][] = $arguments[ 0 ];
                }
            } else {
                throw new phpFastCacheInvalidArgumentException(sprintf('Expected Callable, got "%s"', gettype($arguments[ 0 ])));
            }
        } else {
            throw new \BadMethodCallException('An event must start with "on" such as "onCacheGetItem"');
        }
    }

    /**
     * @param $eventName
     * @param $callbackName
     * @return bool
     */
    public function unbindEventCallback($eventName, $callbackName)
    {
        if (isset($this->events[ $eventName ][ $callbackName ])) {
            unset($this->events[ $eventName ][ $callbackName ]);
            return true;
        }
        return false;
    }
}
