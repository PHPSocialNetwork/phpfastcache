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
use Phpfastcache\Entities\ItemBatch;
use Phpfastcache\Event\EventsInterface;

class CacheGetItemInSlamBatchItemPoolEvent extends AbstractItemPoolEvent
{
    public const EVENT_NAME = EventsInterface::CACHE_GET_ITEM_IN_SLAM_BATCH;

    public function __construct(ExtendedCacheItemPoolInterface $cachePool, protected ItemBatch $cacheItemBatch, protected float|int $cacheSlamsSpendSeconds)
    {
        parent::__construct($cachePool);
    }

    /**
     * @return ItemBatch
     */
    public function getItemBatch(): ItemBatch
    {
        return $this->cacheItemBatch;
    }

    /**
     * @return float|int
     */
    public function getCacheSlamsSpendSeconds(): float|int
    {
        return $this->cacheSlamsSpendSeconds;
    }
}
