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
use Psr\Cache\CacheItemInterface;

/**
 * Class MasterSlaveReplicationCluster
 *
 * @package Phpfastcache\Cluster
 */
class MasterSlaveReplicationCluster extends ClusterPoolAbstract {

  /**
   * ClusterAbstract constructor.
   *
   * @param \Phpfastcache\Cluster\ClusterPoolInterface ...$driverPools
   *
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
   */
  public function __construct(ClusterPoolInterface ... $driverPools) {
    if (\count($driverPools) !== 2) {
      throw new PhpfastcacheInvalidArgumentException('Master/Slave cluster require exactly 2 pools to be working.');
    }
    parent::__construct(...$driverPools);
  }

  /**
   * @inheritDoc
   */
  public function getItem($key) {
    // TODO: Implement getItem() method.
  }

  /**
   * @inheritDoc
   */
  public function getItems(array $keys = []) {
    // TODO: Implement getItems() method.
  }

  /**
   * @inheritDoc
   */
  public function hasItem($key) {
    // TODO: Implement hasItem() method.
  }

  /**
   * @inheritDoc
   */
  public function clear() {
    // TODO: Implement clear() method.
  }

  /**
   * @inheritDoc
   */
  public function deleteItem($key) {
    // TODO: Implement deleteItem() method.
  }

  /**
   * @inheritDoc
   */
  public function deleteItems(array $keys) {
    // TODO: Implement deleteItems() method.
  }

  /**
   * @inheritDoc
   */
  public function save(CacheItemInterface $item) {
    // TODO: Implement save() method.
  }

  /**
   * @inheritDoc
   */
  public function saveDeferred(CacheItemInterface $item) {
    // TODO: Implement saveDeferred() method.
  }

  /**
   * @inheritDoc
   */
  public function commit() {
    // TODO: Implement commit() method.
  }

}
