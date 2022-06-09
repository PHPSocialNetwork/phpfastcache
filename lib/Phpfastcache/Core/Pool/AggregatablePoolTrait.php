<?php

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
