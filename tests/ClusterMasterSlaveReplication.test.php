ClusterFullReplication.test.php<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\Api;
use Phpfastcache\CacheManager;
use Phpfastcache\Cluster\AggregatorInterface;
use Phpfastcache\Cluster\ClusterAggregator;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheRootException;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Master/Slave Replication Cluster');


$clusterAggregator = new ClusterAggregator('test_20');
$unwantedPool = CacheManager::getInstance('Redis');
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Files'));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Sqlite'));
$clusterAggregator->aggregateDriver($unwantedPool);

try{
    $cluster = $clusterAggregator->getCluster(AggregatorInterface::STRATEGY_MASTER_SLAVE);
}catch(PhpfastcacheInvalidArgumentException $e){

}
$clusterAggregator->disaggregateDriver($unwantedPool);
$cluster = $clusterAggregator->getCluster(AggregatorInterface::STRATEGY_MASTER_SLAVE);


$cacheItem = $cluster->getItem('test-test');



$testHelper->terminateTest();
