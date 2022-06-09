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

namespace Phpfastcache\Cluster\Drivers\RandomReplication;

use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Cluster\Drivers\MasterSlaveReplication\Driver as MasterSlaveReplicationDriver;
use Phpfastcache\Event\Event;
use Phpfastcache\Event\EventManagerInterface;
use ReflectionException;
use ReflectionMethod;

class Driver extends MasterSlaveReplicationDriver
{
    /**
     * RandomReplicationCluster constructor.
     * @param string $clusterName
     * @param EventManagerInterface $em
     * @param AggregatablePoolInterface ...$driverPools
     * @throws ReflectionException
     */
    public function __construct(
        string $clusterName,
        EventManagerInterface $em,
        AggregatablePoolInterface ...$driverPools
    ) {
        /** Straight call to ClusterPoolAbstract constructor  */
        (new ReflectionMethod(
            \get_parent_class(\get_parent_class($this)),
            __FUNCTION__
        ))->invoke($this, $clusterName, $em, ...$driverPools);

        $randomPool = $driverPools[\random_int(0, \count($driverPools) - 1)];

        $this->eventManager->dispatch(
            Event::CACHE_REPLICATION_RANDOM_POOL_CHOSEN,
            $this,
            $randomPool
        );

        $this->clusterPools = [$randomPool];
    }

    /**
     * @param callable $operation
     * @return mixed
     */
    protected function makeOperation(callable $operation)
    {
        return $operation($this->getMasterPool());
    }
}
