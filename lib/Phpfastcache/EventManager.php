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

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\Event\EventInterface;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheEventManagerException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Helper\UninstanciableObjectTrait;

class EventManager implements EventManagerInterface
{
    use UninstanciableObjectTrait;

    public const ON_EVERY_EVENT = '__every';

    protected static EventManagerInterface $instance;

    protected bool $isScopedEventManager = false;

    protected ?ExtendedCacheItemPoolInterface $itemPoolContext = null;

    /** @var array<string, array<string, callable>> */
    protected array $listeners = [
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

    /**
     * @param object $event
     * @return object
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function dispatch(object $event)
    {
        if ($event instanceof EventInterface) {
            $eventName = $event::getName();

            if (isset($this->listeners[$eventName]) && $eventName !== self::ON_EVERY_EVENT) {
                foreach ($this->listeners[$eventName] as $listener) {
                    if ($event->isPropagationStopped()) {
                        return $event;
                    }
                    $listener($event);
                }
            }
            foreach ($this->listeners[self::ON_EVERY_EVENT] as $listener) {
                if ($event->isPropagationStopped()) {
                    return $event;
                }
                $listener($event);
            }
            return $event;
        }

        throw new PhpfastcacheInvalidArgumentException(
            sprintf(
                'Method EventManager::dispatch() only accept %s events.',
                EventInterface::class
            )
        );
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheEventManagerException
     */
    public function __call(string $name, array $arguments): void
    {
        if (\str_starts_with($name, 'on')) {
            trigger_error(
                sprintf(
                    'Method "%s()" is deprecated, please use method "%s()" instead. See the migration guide if you seek for detailed help.',
                    $name,
                    $name === 'onEveryEvents' ? 'addGlobalListener' : 'addListener'
                ),
                E_USER_DEPRECATED
            );
            if ($name === 'onEveryEvents') {
                $this->addGlobalListener($arguments[0], $arguments[1] ?? spl_object_hash($arguments[0]));
            } else {
                $this->addListener(\substr($name, 2), $arguments[0]);
            }
        } else {
            throw new PhpfastcacheEventManagerException('An event must start with "on" such as "onCacheGetItem"');
        }
    }

    /**
     * @param callable $callback
     * @param string $callbackName
     * @throws PhpfastcacheEventManagerException
     */
    public function addGlobalListener(callable $callback, string $callbackName): void
    {
        if (trim($callbackName) === '') {
            throw new PhpfastcacheEventManagerException('Parameter $callbackName cannot be empty');
        }
        $this->listeners[self::ON_EVERY_EVENT][$callbackName] = $callback;
    }


    /**
     * @throws PhpfastcacheEventManagerException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function addListener(array|string $events, callable|string $callback): void
    {
        if (is_string($events)) {
            $events = [$events];
        }

        if (!is_callable($callback)) {
            throw new PhpfastcacheInvalidArgumentException(\sprintf('Argument $callback is not callable.'));
        }
        foreach ($events as $event) {
            if (!\preg_match('#^([a-zA-Z])*$#', $event)) {
                throw new PhpfastcacheEventManagerException(\sprintf('Invalid event name "%s"', $event));
            }

            $this->listeners[$event][] = $callback;
        }
    }

    /**
     * @param string $eventName
     * @param string $callbackName
     * @return bool
     */
    public function unbindEventCallback(string $eventName, string $callbackName): bool
    {
        $return = isset($this->listeners[$eventName][$callbackName]);
        unset($this->listeners[$eventName][$callbackName]);

        return $return;
    }

    /**
     * @return bool
     */
    public function unbindAllEventCallbacks(): bool
    {
        $this->listeners = [
            self::ON_EVERY_EVENT => []
        ];

        return true;
    }

    public function __clone(): void
    {
        $this->isScopedEventManager = true;
        $this->unbindAllEventCallbacks();
    }

    /**
     * @param ExtendedCacheItemPoolInterface $pool
     * @return EventManagerInterface
     * @throws PhpfastcacheEventManagerException
     */
    public function getScopedEventManager(ExtendedCacheItemPoolInterface $pool): EventManagerInterface
    {
        return (clone $this)->setItemPoolContext($pool);
    }

    /**
     * @param ExtendedCacheItemPoolInterface $pool
     * @return EventManagerInterface
     * @throws PhpfastcacheEventManagerException
     */
    public function setItemPoolContext(ExtendedCacheItemPoolInterface $pool): EventManagerInterface
    {
        if (!$this->isScopedEventManager) {
            throw new PhpfastcacheEventManagerException('Cannot set itemPool context on unscoped event manager instance.');
        }
        $this->itemPoolContext = $pool;

        $this->addGlobalListener(static function (EventInterface $event) {
            self::getInstance()->dispatch($event);
        }, 'Scoped' . $pool->getDriverName() . spl_object_hash($this));

        return $this;
    }

    /**
     * @param object $event
     * @return array<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        if ($event instanceof EventInterface) {
            return $this->listeners[$event::getName()] ?? [];
        }

        return [];
    }
}
