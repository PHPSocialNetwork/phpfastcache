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

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
 * Class ClusterAbstract
 *
 * @package Phpfastcache\Cluster
 */
abstract class ClusterPoolAbstract implements ClusterPoolInterface
{
    public const STRATEGY = [
        AggregatorInterface::STRATEGY_FULL_REPLICATION => FullReplicationCluster::class,
        AggregatorInterface::STRATEGY_SEMI_REPLICATION => SemiReplicationCluster::class,
        AggregatorInterface::STRATEGY_MASTER_SLAVE => MasterSlaveReplicationCluster::class,
        AggregatorInterface::STRATEGY_RANDOM_REPLICATION => RandomReplicationCluster::class,
    ];

    /**
     * @var ExtendedCacheItemPoolInterface[]
     */
    protected $driverPools;

    /**
     * ClusterAbstract constructor.
     * @param ExtendedCacheItemPoolInterface ...$driverPools
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function __construct(ExtendedCacheItemPoolInterface ... $driverPools)
    {
        if (\count($driverPools) < 2) {
            throw new PhpfastcacheInvalidArgumentException('A cluster requires at least two pools to be working.');
        }
        $this->driverPools = $driverPools;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @param ClusterPoolInterface $driverPool
     * @return CacheItemInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getStandardizedItem(ExtendedCacheItemInterface $item, ClusterPoolInterface $driverPool): CacheItemInterface
    {
        if (!$item->doesItemBelongToThatDriverBackend($driverPool)) {
            return $driverPool->getItem($item->get())
                ->expiresAt($item->getExpirationDate());
        }

        return $item;
    }
}
