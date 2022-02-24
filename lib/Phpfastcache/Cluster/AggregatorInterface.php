<?php

/**
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 * @author  Georges.L (Geolim4)  <contact@geolim4.com>
 */
declare(strict_types=1);

namespace Phpfastcache\Cluster;

use Phpfastcache\Config\ConfigurationOption;

interface AggregatorInterface
{
    /**
     * Full replication mechanism
     *
     * Read on first working (and synchronize if needed, no failure allowed),
     * Write on all (no failure allowed),
     * Delete on all (no failure allowed)
     *
     * Conflict on multiple reads: Keep first found item (but sync the others)
     * Cluster size: 2 minimum, unlimited
     */
    public const STRATEGY_FULL_REPLICATION = 1;

    /**
     * Semi replication mechanism
     *
     * Read first working (but do not synchronize, with partial failure allowed),
     * Write on all (with partial failure allowed)
     * Delete on all (with partial failure allowed)
     *
     * Conflict on multiple reads: Keep first found item
     * Cluster size: 2 minimum, unlimited
     */
    public const STRATEGY_SEMI_REPLICATION = 2;

    /**
     * First pool is master, second is slave
     *
     * Read from master (but do not synchronize, with master failure only allowed)
     * Write on all (with master failure only allowed)
     * Delete on all (with master failure only allowed)
     *
     * Conflict on multiple reads: No, master is exclusive source except if it fails
     * Cluster size: 2 exactly: Master & Slave (Exception if more or less)
     */
    public const STRATEGY_MASTER_SLAVE = 4;

    /**
     * Mostly used for development testing
     *
     * CRUD operations are made on a random-chosen backend from a given cluster.
     * This means you have 1 chance out of (n count of pools) to find an existing cache item
     * but also to write/delete a non-existing item.
     */
    public const STRATEGY_RANDOM_REPLICATION = 8;

    /**
     * AggregatorInterface constructor.
     */
    public function __construct(string $clusterAggregatorName, AggregatablePoolInterface ...$driverPools);

    public function getCluster(int $strategy): ClusterPoolInterface;

    public function aggregateDriverByName(string $driverName, ?ConfigurationOption $driverConfig = null): void;

    public function aggregateDriver(AggregatablePoolInterface $driverPool): void;

    public function disaggregateDriver(AggregatablePoolInterface $driverPool): void;
}
