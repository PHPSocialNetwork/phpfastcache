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

namespace Phpfastcache\Core\Pool;

use Phpfastcache\Cluster\ClusterPoolInterface;

trait AggregatablePoolTrait
{
    protected ?ClusterPoolInterface $clusterPool = null;

    public function isAggregatedBy(): ?ClusterPoolInterface
    {
        return $this->clusterPool;
    }

    public function setAggregatedBy(ClusterPoolInterface $clusterPool): static
    {
        $this->clusterPool = $clusterPool;

        return $this;
    }
}
