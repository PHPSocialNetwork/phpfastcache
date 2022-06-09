<?php

/**
 *
 * This file is part of Phpfastcache.
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

interface AggregatablePoolInterface extends ExtendedCacheItemPoolInterface
{
    public function isAggregatedBy(): ?ClusterPoolInterface;

    public function setAggregatedBy(ClusterPoolInterface $clusterPool): static;
}
