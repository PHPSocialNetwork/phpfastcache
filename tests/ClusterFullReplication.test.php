ClusterFullReplication.test.php<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\Api;
use Phpfastcache\CacheManager;
use Phpfastcache\Cluster\AggregatorInterface;
use Phpfastcache\Cluster\ClusterAggregator;
use Phpfastcache\Exceptions\PhpfastcacheRootException;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Full Replication Cluster');

$clusterAggregator = new ClusterAggregator('test_20');
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Redis'));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Files'));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Sqlite'));
$cluster = $clusterAggregator->getCluster(AggregatorInterface::STRATEGY_FULL_REPLICATION);
$cluster->clear();

$testHelper->runCRUDTests($cluster);

$testHelper->terminateTest();
