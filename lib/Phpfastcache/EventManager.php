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

namespace Phpfastcache;

use BadMethodCallException;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

/**
 * Class CacheManager
 * @package phpFastCache
 *
 * == ItemPool Events ==
 * @method Void onCacheGetItem() onCacheGetItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheDeleteItem() onCacheDeleteItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheSaveItem() onCacheSaveItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheSaveDeferredItem() onCacheSaveDeferredItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheCommitItem() onCacheCommitItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheClearItem() onCacheClearItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheWriteFileOnDisk() onCacheWriteFileOnDisk(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheGetItemInSlamBatch() onCacheGetItemInSlamBatch(Callable $callable, ?string $callbackName = null)
 *
 * == ItemPool Events (Cluster) ==
 * @method Void onCacheReplicationSlaveFallback() onCacheReplicationSlaveFallback(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheReplicationRandomPoolChosen() onCacheReplicationRandomPoolChosen(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheClusterBuilt() onCacheClusterBuilt(Callable $callable, ?string $callbackName = null)
 *
 * == Item Events ==
 * @method Void onCacheItemSet() onCacheItemSet(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheItemExpireAt() onCacheItemExpireAt(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheItemExpireAfter() onCacheItemExpireAfter(Callable $callable, ?string $callbackName = null)
 *
 */
class EventManager implements EventManagerInterface
{
    public const ON_EVERY_EVENT = '__every';

    /**
     * @var $this
     */
    protected static $instance;

    /**
     * @var array
     */
    protected $events = [
        self::ON_EVERY_EVENT => []
    ];

    /**
     * EventManager constructor.
     */
    final protected function __construct()
    {
        // The constructor should not be instantiated externally
    }

    /**
     * @return EventManagerInterface
     */
    public static function getInstance(): EventManagerInterface
    {
        return (self::$instance ?: self::$instance = new self);
    }

    /**
     * @param string $eventName
     * @param array ...$args
     */
    public function dispatch(string $eventName, ...$args): void
    {
        /**
         * Replace array_key_exists by isset
         * due to performance issue on huge
         * loop dispatching operations
         */
        if (isset($this->events[$eventName]) && $eventName !== self::ON_EVERY_EVENT) {
            foreach ($this->events[$eventName] as $event) {
                $event(... $args);
            }
        }
        foreach ($this->events[self::ON_EVERY_EVENT] as $event) {
            $event($eventName, ...$args);
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @throws PhpfastcacheInvalidArgumentException
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $arguments): void
    {
        if (\strpos($name, 'on') === 0) {
            $name = \substr($name, 2);
            if (\is_callable($arguments[0])) {
                if (isset($arguments[1]) && \is_string($arguments[0])) {
                    $this->events[$name][$arguments[1]] = $arguments[0];
                } else {
                    $this->events[$name][] = $arguments[0];
                }
            } else {
                throw new PhpfastcacheInvalidArgumentException(\sprintf('Expected Callable, got "%s"', \gettype($arguments[0])));
            }
        } else {
            throw new BadMethodCallException('An event must start with "on" such as "onCacheGetItem"');
        }
    }

    /**
     * @param callable $callback
     * @param string $callbackName
     */
    public function onEveryEvents(callable $callback, string $callbackName): void
    {
        $this->events[self::ON_EVERY_EVENT][$callbackName] = $callback;
    }

    /**
     * @param string $eventName
     * @param string $callbackName
     * @return bool
     */
    public function unbindEventCallback(string $eventName, string $callbackName): bool
    {
        $return = isset($this->events[$eventName][$callbackName]);
        unset($this->events[$eventName][$callbackName]);

        return $return;
    }
}
