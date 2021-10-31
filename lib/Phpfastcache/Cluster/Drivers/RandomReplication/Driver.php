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

namespace Phpfastcache\Cluster\Drivers\RandomReplication;

use Phpfastcache\Cluster\Drivers\MasterSlaveReplication\Driver as MasterSlaveReplicationDriver;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventManagerInterface;
use ReflectionException;
use ReflectionMethod;

class Driver extends MasterSlaveReplicationDriver
{
    /**
     * RandomReplicationCluster constructor.
     * @param string $clusterName
     * @param ExtendedCacheItemPoolInterface ...$driverPools
     * @throws ReflectionException
     */
    public function __construct(string $clusterName, EventManagerInterface $em, ExtendedCacheItemPoolInterface ...$driverPools)
    {
        (new ReflectionMethod(\get_parent_class(\get_parent_class($this)), __FUNCTION__))
            ->invoke($this, $clusterName, $em, ...$driverPools);
        $randomPool = $driverPools[\random_int(0, \count($driverPools) - 1)];

        $this->eventManager->dispatch(
            'CacheReplicationRandomPoolChosen',
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
