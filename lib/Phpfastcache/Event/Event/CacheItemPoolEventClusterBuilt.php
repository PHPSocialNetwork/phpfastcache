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

use Phpfastcache\Cluster\AggregatorInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventsInterface;

class CacheItemPoolEventClusterBuilt extends AbstractItemPoolEvent
{
    public const EVENT_NAME = EventsInterface::CACHE_CLUSTER_BUILT;

    public function __construct(ExtendedCacheItemPoolInterface $cachePool, protected AggregatorInterface $clusterAggregator)
    {
        parent::__construct($cachePool);
    }

    /**
     * @return AggregatorInterface
     */
    public function getClusterAggregator(): AggregatorInterface
    {
        return $this->clusterAggregator;
    }
}
