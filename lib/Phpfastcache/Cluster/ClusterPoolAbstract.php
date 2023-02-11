<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Georges.L (Geolim4) <contact@geolim4.com>
 *
 */

declare(strict_types=1);

namespace Phpfastcache\Cluster;

use Phpfastcache\Cluster\Drivers\FullReplication\Driver as FullReplicationCluster;
use Phpfastcache\Cluster\Drivers\MasterSlaveReplication\Driver as MasterSlaveReplicationCluster;
use Phpfastcache\Cluster\Drivers\RandomReplication\Driver as RandomReplicationCluster;
use Phpfastcache\Cluster\Drivers\SemiReplication\Driver as SemiReplicationCluster;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\EventManager;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;

abstract class ClusterPoolAbstract implements ClusterPoolInterface
{
    use TaggableCacheItemPoolTrait;
    use ClusterPoolTrait {
        TaggableCacheItemPoolTrait::__construct as private __parentConstruct;
    }

    public const STRATEGY = [
        AggregatorInterface::STRATEGY_FULL_REPLICATION => FullReplicationCluster::class,
        AggregatorInterface::STRATEGY_SEMI_REPLICATION => SemiReplicationCluster::class,
        AggregatorInterface::STRATEGY_MASTER_SLAVE => MasterSlaveReplicationCluster::class,
        AggregatorInterface::STRATEGY_RANDOM_REPLICATION => RandomReplicationCluster::class,
    ];

    /**
     * @var AggregatablePoolInterface[]
     */
    protected array $clusterPools;

    /**
     * ClusterPoolAbstract constructor.
     * @param string $clusterName
     * @param EventManagerInterface $em
     * @param ExtendedCacheItemPoolInterface ...$driverPools
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverConnectException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheIOException
     */
    public function __construct(string $clusterName, EventManagerInterface $em, AggregatablePoolInterface ...$driverPools)
    {
        if (count($driverPools) < 2) {
            throw new PhpfastcacheInvalidArgumentException('A cluster requires at least two pools to be working.');
        }
        $this->clusterPools = $driverPools;
        $this->__parentConstruct(new ConfigurationOption(), $clusterName, $em);
        $this->setEventManager(EventManager::getInstance());
        $this->setClusterPoolsAggregator();
    }

    protected function setClusterPoolsAggregator(): void
    {
        foreach ($this->clusterPools as $clusterPool) {
            $clusterPool->setAggregatedBy($this);
        }
    }

    /**
     * @inheritDoc
     */
    public function getIO(): DriverIO
    {
        $io = new DriverIO();
        foreach ($this->clusterPools as $clusterPool) {
            $io->setReadHit($io->getReadHit() + $clusterPool->getIO()->getReadHit())
                ->setReadMiss($io->getReadMiss() + $clusterPool->getIO()->getReadMiss())
                ->setWriteHit($io->getWriteHit() + $clusterPool->getIO()->getWriteHit());
        }
        return $io;
    }

    /**
     * @inheritDoc
     */
    public function getClusterPools(): array
    {
        return $this->clusterPools;
    }

    /**
     * @inheritDoc
     */
    public function getConfigs(): array
    {
        $configs = [];

        foreach ($this->getClusterPools() as $clusterPool) {
            $configs[$clusterPool->getDriverName()] = $clusterPool->getConfig();
        }

        return $configs;
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];

        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }
    /**
     * Shared method used by All Clusters
     */

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys): bool
    {
        $hasDeletedOnce = false;
        foreach ($this->clusterPools as $driverPool) {
            if ($result = $driverPool->deleteItems($keys)) {
                $hasDeletedOnce = $result;
            }
        }
        // Return true only if at least one backend confirmed the "clear" operation
        return $hasDeletedOnce;
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws InvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        /** @var ExtendedCacheItemInterface $item */
        $hasSavedOnce = false;
        foreach ($this->clusterPools as $driverPool) {
            $poolItem = $this->getStandardizedItem($item, $driverPool);
            if ($result = $driverPool->saveDeferred($poolItem)) {
                $hasSavedOnce = $result;
            }
        }
        // Return true only if at least one backend confirmed the "commit" operation
        return $hasSavedOnce;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @param ExtendedCacheItemPoolInterface $driverPool
     * @return ExtendedCacheItemInterface
     * @throws InvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function getStandardizedItem(ExtendedCacheItemInterface $item, ExtendedCacheItemPoolInterface $driverPool): ExtendedCacheItemInterface
    {
        if (!$item->doesItemBelongToThatDriverBackend($driverPool)) {
            /**
             * Avoid infinite loop
             */
            if ($driverPool === $this) {
                /** @var ExtendedCacheItemInterface $itemPool */
                $itemClass = $driverPool::getItemClass();
                $itemPool = new $itemClass($this, $item->getKey(), $this->getEventManager());
                $item->cloneInto($itemPool, $driverPool);

                return $itemPool;
            }

            $itemPool = $driverPool->getItem($item->getKey());
            $item->cloneInto($itemPool, $driverPool);

            return $itemPool;
        }

        return $item->setEventManager($this->getEventManager());
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $stats = new DriverStatistic();
        $stats->setInfo(
            sprintf(
                'Using %d pool(s): %s',
                \count($this->clusterPools),
                \implode(
                    ', ',
                    \array_map(
                        static fn (ExtendedCacheItemPoolInterface $pool) => \get_class($pool),
                        $this->clusterPools
                    )
                )
            )
        );

        $stats->setSize(
            (int)\array_sum(
                \array_map(
                    static fn (ExtendedCacheItemPoolInterface $pool) => $pool->getStats()->getSize(),
                    $this->clusterPools
                )
            )
        );

        $stats->setData(
            \implode(
                ', ',
                \array_map(
                    static fn (ExtendedCacheItemPoolInterface $pool) => $pool->getStats()->getData(),
                    $this->clusterPools
                )
            )
        );

        return $stats;
    }
}
