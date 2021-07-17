<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Cluster\AggregatorInterface;
use Phpfastcache\Cluster\ClusterAggregator;
use Phpfastcache\Drivers\Files\Config as FilesConfig;
use Phpfastcache\Drivers\Sqlite\Config as SqliteConfig;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mock/Autoload.php';
$testHelper = new TestHelper('Master/Slave Replication Cluster');

CacheManager::clearInstances();

$clusterAggregator = new ClusterAggregator('test_20');
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Redis'));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Sqlite', new SqliteConfig(['securityKey' => 'unit_tests'])));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Files', new FilesConfig(['securityKey' => 'unit_tests'])));
$cluster = $clusterAggregator->getCluster(AggregatorInterface::STRATEGY_RANDOM_REPLICATION);
$cluster->clear();

$testHelper->runCRUDTests($cluster);

$testHelper->terminateTest();
