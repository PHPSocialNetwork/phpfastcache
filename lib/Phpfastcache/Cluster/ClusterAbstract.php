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

use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

/**
 * Class ClusterAbstract
 *
 * @package Phpfastcache\Cluster
 */
abstract class ClusterAbstract implements ClusterInterface {

  public const STRATEGY = [
    AggregatorInterface::STRATEGY_FULL_REPLICATION   => FullReplicationCluster::class,
    AggregatorInterface::STRATEGY_SEMI_REPLICATION   => SemiReplicationCluster::class,
    AggregatorInterface::STRATEGY_MASTER_SLAVE       => MasterSlaveReplicationCluster::class,
    AggregatorInterface::STRATEGY_RANDOM_REPLICATION => RandomReplicationCluster::class,
  ];

  /**
   * ClusterAbstract constructor.
   *
   * @param \Phpfastcache\Cluster\ClusterInterface ...$driverPools
   *
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
   */
  public function __construct(ClusterInterface ... $driverPools) {
    if(\count($driverPools) < 2){
      throw new PhpfastcacheInvalidArgumentException('A cluster requires at least two pools to be working.');
    }
  }
}
