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

interface ClusterInterface extends CacheItemPoolInterface{
  // Read on first working (and synchronize if needed, no failure allowed), write on all (no failure allowed), delete on all (no failure allowed)
  // Conflict on multiple reads: Exception
  // Cluster size: 2 minimum, unlimited
  public const STRATEGY_FULL_REPLICATION = 1;

  // Read first working (but do not synchronize, with partial failure allowed), write on all (with partial failure allowed), delete on all (with partial failure allowed)
  // Conflict on multiple reads: Keep first found data
  // Cluster size: 2 minimum, unlimited
  public const STRATEGY_SEMI_REPLICATION = 2;

  // First is master
  // Read from master (but do not synchronize, with master failure only allowed), write on all (with master failure only allowed), delete on all (with master failure only allowed)
  // Conflict on multiple reads: No, master is source
  // Cluster size: 2 exactly: Master & Slave (Exception if more or less)
  public const STRATEGY_MASTER_SLAVE = 4;

  // CRUD operations are made on a random-chosen backend during the php script execution.
  // This means you have 1 chance out of (n count of cluster) to find an existing cache item !!
  public const STRATEGY_RANDOM_REPLICATION = 8;

  public function __construct(string $clusterName, CacheItemPoolInterface ...$driverPools);

    /**
   * @return string
   */
  public function getClusterName(): string;

  public function aggregateNewDriver(string $driverName, ConfigurationOption $driverConfig = NULL);

  public function aggregateDriver(CacheItemPoolInterface $driverName);
}
