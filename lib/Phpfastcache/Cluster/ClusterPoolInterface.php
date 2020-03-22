<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author  Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Cluster;

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;

/**
 * Interface ClusterInterface
 *
 * @package Phpfastcache\Cluster
 */
interface ClusterPoolInterface extends ExtendedCacheItemPoolInterface
{
    /**
     * @return ExtendedCacheItemPoolInterface[]
     */
    public function getClusterPools(): array;
}
