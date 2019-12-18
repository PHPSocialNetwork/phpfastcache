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

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * Class ClusterAggregator
 *
 * @package Phpfastcache\Cluster
 */
abstract class ClusterAggregator implements AggregatorInterface {

  protected $driverPools;

  /**
   * @var ClusterInterface
   */
  protected $cluster;

  /**
   * ClusterAggregator constructor.
   *
   * @param string                                          $clusterName
   * @param \Phpfastcache\Cluster\AggregatablePoolInterface ...$driverPools
   *
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheLogicException
   */
  public function __construct(string $clusterName, AggregatablePoolInterface ...$driverPools) {
    $clusterName = \trim($clusterName);
    if(empty($clusterName)){
      throw new PhpfastcacheInvalidArgumentException('Cluster name empty');
    }

    foreach ($driverPools as $driverPool) {
      $this->aggregateDriver($driverPool);
    }
  }

  /**
   * @param string                                        $driverName
   * @param \Phpfastcache\Config\ConfigurationOption|NULL $driverConfig
   *
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheLogicException
   */
  public function aggregateNewDriver(string $driverName, ConfigurationOption $driverConfig = NULL): void {
    if($this->cluster){
      throw new PhpfastcacheLogicException('The cluster has been already build, cannot aggregate more pools.');
    }
    $this->aggregateDriver(
      CacheManager::getInstance($driverName, $driverConfig)
    );
  }

  /**
   * @param \Phpfastcache\Cluster\AggregatablePoolInterface $driverPool
   *
   * @throws \Phpfastcache\Exceptions\PhpfastcacheLogicException
   */
  public function aggregateDriver(AggregatablePoolInterface $driverPool): void {
    if($this->cluster){
      throw new PhpfastcacheLogicException('The cluster has been already build, cannot aggregate more pools.');
    }

    $splHash = \spl_object_hash($driverPool);
    if(!isset($this->driverPools[$splHash])){
      if($driverPool instanceof ClusterInterface){
        throw new PhpfastcacheLogicException('Recursive cluster aggregation is not allowed !');
      }

      $this->driverPools[$splHash] = $driverPool;
    }else{
      throw new PhpfastcacheLogicException('This pool has been already aggregated !');
    }
  }

  /**
   * @param int $strategy
   *
   * @return \Phpfastcache\Cluster\ClusterInterface
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
   */
  public function getCluster(int $strategy = AggregatorInterface::STRATEGY_FULL_REPLICATION): ClusterInterface{
    if(isset(ClusterAbstract::STRATEGY[$strategy])){
      if(!$this->cluster){
        $this->cluster = new (ClusterAbstract::STRATEGY[$strategy])(...$this->driverPools);
      }
    }else{
      throw new PhpfastcacheInvalidArgumentException('Unknown cluster strategy');
    }

    return $this->cluster;
  }
}
