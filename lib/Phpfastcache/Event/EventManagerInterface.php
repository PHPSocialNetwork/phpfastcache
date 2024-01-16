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
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheEventManagerException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * @see EventsInterface for the list of available events.
 */
interface EventManagerInterface extends EventDispatcherInterface, ListenerProviderInterface
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
    public function addGlobalListener(callable $callback, string $callbackName): void;

    /**
     * @param string[]|string $events
     * @param callable $callback
     */
    public function addListener(array|string $events, callable $callback): void;

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

    /**
     * @param ExtendedCacheItemPoolInterface $pool
     * @return EventManagerInterface
     */
    public function getScopedEventManager(ExtendedCacheItemPoolInterface $pool): EventManagerInterface;

    /**
     * @param ExtendedCacheItemPoolInterface $pool
     * @return EventManagerInterface
     * @throws PhpfastcacheEventManagerException
     */
    public function setItemPoolContext(ExtendedCacheItemPoolInterface $pool): EventManagerInterface;
}
