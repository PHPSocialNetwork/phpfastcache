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

/**
 * @suppressWarnings(PHPMD.LongClassName)
 */
class CacheReplicationSlaveFallbackItemPoolEvent extends AbstractItemPoolEvent
{
    public const EVENT_NAME = EventsInterface::CACHE_REPLICATION_SLAVE_FALLBACK;

    public function __construct(ExtendedCacheItemPoolInterface $itemPool, protected string $methodCaller)
    {
        parent::__construct($itemPool);
    }

    /**
     * @return string
     */
    public function getMethodCaller(): string
    {
        return $this->methodCaller;
    }
}
