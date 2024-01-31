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

namespace Phpfastcache\Event\Event;

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventsInterface;

class CacheDriverConnectedEvent extends AbstractItemPoolEvent
{
    public const EVENT_NAME = EventsInterface::CACHE_DRIVER_CONNECTED;

    public function __construct(ExtendedCacheItemPoolInterface $cachePool, protected ?object $driverInstance)
    {
        parent::__construct($cachePool);
    }

    /**
     * @return object|null
     */
    public function getDriverInstance(): ?object
    {
        return $this->driverInstance;
    }
}
