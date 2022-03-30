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

use Phpfastcache\Cluster\Drivers\{FullReplication\FullReplicationCluster,
    MasterSlaveReplication\MasterSlaveReplicationCluster,
    RandomReplication\RandomReplicationCluster,
    SemiReplication\SemiReplicationCluster
};
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\{Item\ExtendedCacheItemInterface, Pool\DriverBaseTrait, Pool\ExtendedCacheItemPoolInterface};
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\EventManager;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Psr\Cache\{CacheItemInterface, InvalidArgumentException};
use ReflectionException;

/**
 * Class ClusterAbstract
 *
 * @package Phpfastcache\Cluster
 */
abstract class ClusterPoolAbstract implements ClusterPoolInterface
{
    use DriverBaseTrait;
    use ClusterPoolTrait {
        DriverBaseTrait::__construct as private __parentConstruct;
    }

    public const STRATEGY = [
        AggregatorInterface::STRATEGY_FULL_REPLICATION => FullReplicationCluster::class,
        AggregatorInterface::STRATEGY_SEMI_REPLICATION => SemiReplicationCluster::class,
        AggregatorInterface::STRATEGY_MASTER_SLAVE => MasterSlaveReplicationCluster::class,
        AggregatorInterface::STRATEGY_RANDOM_REPLICATION => RandomReplicationCluster::class,
    ];

    /**
     * @var ExtendedCacheItemPoolInterface[]
     */
    protected $clusterPools;

    /**
     * ClusterPoolAbstract constructor.
     * @param string $clusterName
     * @param ExtendedCacheItemPoolInterface ...$driverPools
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverConnectException
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws ReflectionException
     */
    public function __construct(string $clusterName, ExtendedCacheItemPoolInterface ...$driverPools)
    {
        if (count($driverPools) < 2) {
            throw new PhpfastcacheInvalidArgumentException('A cluster requires at least two pools to be working.');
        }
        $this->clusterPools = $driverPools;
        $this->__parentConstruct(new ConfigurationOption(), $clusterName);
        $this->setEventManager(EventManager::getInstance());
    }

    /**
     * @inheritDoc
     */
    public function getIO(): DriverIO
    {
        $IO = new DriverIO();
        foreach ($this->clusterPools as $clusterPool) {
            $IO->setReadHit($IO->getReadHit() + $clusterPool->getIO()->getReadHit())
                ->setReadMiss($IO->getReadMiss() + $clusterPool->getIO()->getReadMiss())
                ->setWriteHit($IO->getWriteHit() + $clusterPool->getIO()->getWriteHit());
        }
        return $IO;
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
    public function getItems(array $keys = [])
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
    public function deleteItems(array $keys)
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
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item)
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
     * @return CacheItemInterface
     * @throws InvalidArgumentException
     */
    protected function getStandardizedItem(ExtendedCacheItemInterface $item, ExtendedCacheItemPoolInterface $driverPool): CacheItemInterface
    {
        if (!$item->doesItemBelongToThatDriverBackend($driverPool)) {
            /**
             * Avoid infinite loop
             */
            if ($driverPool === $this) {
                /** @var ExtendedCacheItemInterface $itemPool */
                $itemClass = $driverPool->getClassNamespace() . '\\' . 'Item';
                $itemPool = new $itemClass($this, $item->getKey(), $this->getEventManager());
                $itemPool->set($item->get())
                    ->setHit($item->isHit())
                    ->setTags($item->getTags())
                    ->expiresAt($item->getExpirationDate())
                    ->setDriver($driverPool);
                return $itemPool;
            }
            return $driverPool->getItem($item->getKey())
                ->setEventManager($this->getEventManager())
                ->set($item->get())
                ->setHit($item->isHit())
                ->setTags($item->getTags())
                ->expiresAt($item->getExpirationDate())
                ->setDriver($driverPool);
        }

        return $item->setEventManager($this->getEventManager());
    }

    /**
     * Interfaced methods that needs to be faked
     */

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
                        static function (ExtendedCacheItemPoolInterface $pool) {
                            return \get_class($pool);
                        },
                        $this->clusterPools
                    )
                )
            )
        );

        $stats->setSize(
            (int)\array_sum(
                \array_map(
                    static function (ExtendedCacheItemPoolInterface $pool) {
                        return $pool->getStats()->getSize();
                    },
                    $this->clusterPools
                )
            )
        );

        $stats->setData(
            (int)\array_map(
                static function (ExtendedCacheItemPoolInterface $pool) {
                    return $pool->getStats()->getData();
                },
                $this->clusterPools
            )
        );

        return $stats;
    }
}
