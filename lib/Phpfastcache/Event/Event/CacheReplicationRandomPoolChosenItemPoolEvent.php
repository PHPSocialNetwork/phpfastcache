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
class CacheReplicationRandomPoolChosenItemPoolEvent extends AbstractItemPoolEvent
{
    public const EVENT_NAME = EventsInterface::CACHE_REPLICATION_RANDOM_POOL_CHOSEN;

    public function __construct(ExtendedCacheItemPoolInterface $cachePool, protected ExtendedCacheItemPoolInterface $randomPool)
    {
        parent::__construct($cachePool);
    }

    /**
     * @return ExtendedCacheItemPoolInterface
     */
    public function getRandomPool(): ExtendedCacheItemPoolInterface
    {
        return $this->randomPool;
    }
}
