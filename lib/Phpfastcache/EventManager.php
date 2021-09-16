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

namespace Phpfastcache;

use BadMethodCallException;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

/**
 * == ItemPool Events ==
 * @method Void onCacheGetItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheDeleteItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheSaveItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheSaveMultipleItems(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheSaveDeferredItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheCommitItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheClearItem(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheWriteFileOnDisk(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheGetItemInSlamBatch(Callable $callable, ?string $callbackName = null)
 *
 * == ItemPool Events (Cluster) ==
 * @method Void onCacheReplicationSlaveFallback(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheReplicationRandomPoolChosen(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheClusterBuilt(Callable $callable, ?string $callbackName = null)
 *
 * == Item Events ==
 * @method Void onCacheItemSet(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheItemExpireAt(Callable $callable, ?string $callbackName = null)
 * @method Void onCacheItemExpireAfter(Callable $callable, ?string $callbackName = null)
 *
 */
class EventManager implements EventManagerInterface
{
    public const ON_EVERY_EVENT = '__every';

    protected static self $instance;

    protected array $events = [
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
     * @return static
     */
    public static function getInstance(): static
    {
        return (self::$instance ?? self::$instance = new static);
    }

    /**
     * @param string $eventName
     * @param array $args
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
        if (str_starts_with($name, 'on')) {
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

    /**
     * @return bool
     */
    public function unbindAllEventCallbacks(): bool
    {
        $this->events =  [
            self::ON_EVERY_EVENT => []
        ];

        return true;
    }
}
