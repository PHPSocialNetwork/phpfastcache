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

use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheEventManagerException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Helper\UninstanciableObjectTrait;

class EventManager implements EventManagerInterface
{
    use UninstanciableObjectTrait;

    public const ON_EVERY_EVENT = '__every';

    protected static EventManagerInterface $instance;

    /** @var array<string, array<string, callable>> */
    protected array $events = [
        self::ON_EVERY_EVENT => []
    ];

    /**
     * @return EventManagerInterface
     */
    public static function getInstance(): EventManagerInterface
    {
        return (self::$instance ?? self::$instance = new static());
    }

    /**
     * @param EventManagerInterface $eventManagerInstance
     * @return void
     */
    public static function setInstance(EventManagerInterface $eventManagerInstance): void
    {
        self::$instance = $eventManagerInstance;
    }

    public function dispatch(string $eventName, ...$args): void
    {
        /**
         * Replace array_key_exists by isset
         * due to performance issue on huge
         * loop dispatching operations
         */
        if (isset($this->events[$eventName]) && $eventName !== self::ON_EVERY_EVENT) {
            $loopArgs = array_merge($args, [$eventName]);
            foreach ($this->events[$eventName] as $event) {
                $event(...$loopArgs);
            }
        }
        foreach ($this->events[self::ON_EVERY_EVENT] as $event) {
            $event($eventName, ...$args);
        }
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheEventManagerException
     */
    public function __call(string $name, array $arguments): void
    {
        if (\str_starts_with($name, 'on')) {
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
            throw new PhpfastcacheEventManagerException('An event must start with "on" such as "onCacheGetItem"');
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
     * @throws PhpfastcacheEventManagerException
     */
    public function on(array $events, callable $callback): void
    {
        foreach ($events as $event) {
            if (!\preg_match('#^([a-zA-Z])*$#', $event)) {
                throw new PhpfastcacheEventManagerException(\sprintf('Invalid event name "%s"', $event));
            }

            $this->{'on' . \ucfirst($event)}($callback);
        }
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
        $this->events = [
            self::ON_EVERY_EVENT => []
        ];

        return true;
    }
}
