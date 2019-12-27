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

use Phpfastcache\Cluster\Drivers\FullReplication\FullReplicationCluster;
use Phpfastcache\Cluster\Drivers\MasterSlaveReplication\MasterSlaveReplicationCluster;
use Phpfastcache\Cluster\Drivers\SemiReplication\SemiReplicationCluster;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\DriverBaseTrait;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\EventManager;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
 * Class ClusterAbstract
 *
 * @package Phpfastcache\Cluster
 */
abstract class ClusterPoolAbstract implements ClusterPoolInterface
{
    use DriverBaseTrait {
        DriverBaseTrait::__construct as private __parentConstruct;
    }

    public const STRATEGY = [
        AggregatorInterface::STRATEGY_FULL_REPLICATION => FullReplicationCluster::class,
        AggregatorInterface::STRATEGY_SEMI_REPLICATION => SemiReplicationCluster::class,
        AggregatorInterface::STRATEGY_MASTER_SLAVE => MasterSlaveReplicationCluster::class,
        // AggregatorInterface::STRATEGY_RANDOM_REPLICATION => RandomReplicationCluster::class,
    ];

    /**
     * @var ExtendedCacheItemPoolInterface[]
     */
    protected $driverPools;

    /**
     * ClusterPoolAbstract constructor.
     * @param string $clusterName
     * @param ExtendedCacheItemPoolInterface ...$driverPools
     * @throws PhpfastcacheInvalidArgumentException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverConnectException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
     * @throws \ReflectionException
     */
    public function __construct(string $clusterName, ExtendedCacheItemPoolInterface ...$driverPools)
    {
        if (\count($driverPools) < 2) {
            throw new PhpfastcacheInvalidArgumentException('A cluster requires at least two pools to be working.');
        }
        $this->driverPools = $driverPools;
        $this->__parentConstruct(new ConfigurationOption(), $clusterName);
        $this->setEventManager(EventManager::getInstance());
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = [])
    {
        $items = [];

        foreach ($keys as $key) {
            $items[] = $this->getItem($key);
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
        foreach ($this->driverPools as $driverPool) {
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
        foreach ($this->driverPools as $driverPool) {
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
     * @throws \Psr\Cache\InvalidArgumentException
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
                $itemPool = new $itemClass($this, $item->getKey());
                $itemPool->setEventManager($this->getEventManager())
                    ->set($item->get())
                    ->expiresAt($item->getExpirationDate())
                    ->setDriver($driverPool);
                return $itemPool;
            }
            return $driverPool->getItem($item->getKey())
                ->set($item->get())
                ->setEventManager($this->getEventManager())
                ->expiresAt($item->getExpirationDate())
                ->setDriver($driverPool);
        }
        return $item;
    }

    /**
     * Interfaced methods that needs to be faked
     */

    /**
     * @return DriverStatistic
     * @throws \Cassandra\Exception
     */
    public function getStats(): DriverStatistic
    {
        // @todo later :)
    }

    /**
     * @return bool
     */
    protected function driverCheck(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        return true;
    }

    /**
     * @param CacheItemInterface $item
     * @return null
     */
    protected function driverRead(CacheItemInterface $item)
    {
        return null;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return true;
    }
}
