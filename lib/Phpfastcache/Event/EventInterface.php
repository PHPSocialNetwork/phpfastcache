<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 29/04/2018
 * Time: 23:28
 */

namespace Phpfastcache\Event;

use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

interface EventInterface
{
    /**
     * @return self
     */
    public static function getInstance(): self;

    /**
     * @param string $eventName
     * @param array ...$args
     */
    public function dispatch(string $eventName, ...$args);

    /**
     * @param string $name
     * @param array $arguments
     * @throws PhpfastcacheInvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function __call(string $name, array $arguments);

    /**
     * @param string $eventName
     * @param string $callbackName
     * @return bool
     */
    public function unbindEventCallback(string $eventName, string $callbackName): bool;
}