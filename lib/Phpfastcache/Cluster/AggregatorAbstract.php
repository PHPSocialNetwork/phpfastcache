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

use Phpfastcache\Config\ConfigurationOption;
use Psr\Cache\CacheItemPoolInterface;

abstract class AggregatorAbstract implements ClusterInterface {

  /**
   * @var string
   */
  protected $custerName;

  /**
   * AggregatorCluster constructor.
   *
   * @param string                            $clusterName
   * @param \Psr\Cache\CacheItemPoolInterface ...$driverPools
   */
  public function __construct(string $clusterName, CacheItemPoolInterface ...$driverPools) {
    $this->custerName = $clusterName;

    foreach ($driverPools as $driverPool) {
      $this->aggregateDriver($driverPool);
    }
  }

  public function aggregateNewDriver(string $driverName, ConfigurationOption $driverConfig = NULL) {
    // @todo
  }

  public function aggregateDriver(CacheItemPoolInterface $driverPool) {
    // @todo
  }
}
