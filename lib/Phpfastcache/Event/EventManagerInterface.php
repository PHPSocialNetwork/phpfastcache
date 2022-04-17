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

namespace Phpfastcache\Event;

use BadMethodCallException;
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
 * == Driver-specific events ==
 * @method Void onArangodbConnection(Callable $callable, ?string $callbackName = null)
 * @method Void onArangodbCollectionParams(Callable $callable, ?string $callbackName = null)
 * @method Void onDynamodbCreateTable(Callable $callable, ?string $callbackName = null)
 * @method Void onSolrBuildEndpoint(Callable $callable, ?string $callbackName = null)
 */
interface EventManagerInterface
{
    /**
     * @return self
     */
    public static function getInstance(): EventManagerInterface;

    /**
     * @param EventManagerInterface $eventManagerInstance
     * @return void
     */
    public static function setInstance(EventManagerInterface $eventManagerInstance): void;

    /**
     * @param string $eventName
     * @param array<mixed> $args
     */
    public function dispatch(string $eventName, ...$args): void;

    /**
     * @param string $name
     * @param array<mixed> $arguments
     * @throws PhpfastcacheInvalidArgumentException
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $arguments): void;

    /**
     * @param callable $callback
     * @param string $callbackName
     */
    public function onEveryEvents(callable $callback, string $callbackName): void;

    /**
     * @param string[] $events
     * @param callable $callback
     */
    public function on(array $events, callable $callback): void;

    /**
     * @param string $eventName
     * @param string $callbackName
     * @return bool
     */
    public function unbindEventCallback(string $eventName, string $callbackName): bool;

    /**
     * @return bool
     */
    public function unbindAllEventCallbacks(): bool;
}
