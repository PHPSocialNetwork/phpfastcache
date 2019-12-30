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

namespace Phpfastcache\Event;

use BadMethodCallException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

/**
 * Interface EventManagerInterface
 * @package Phpfastcache\Event
 */
interface EventManagerInterface
{
    /**
     * @return self
     */
    public static function getInstance(): self;

    /**
     * @param string $eventName
     * @param array ...$args
     */
    public function dispatch(string $eventName, ...$args): void;

    /**
     * @param string $name
     * @param array $arguments
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
     * @param string $eventName
     * @param string $callbackName
     * @return bool
     */
    public function unbindEventCallback(string $eventName, string $callbackName): bool;
}