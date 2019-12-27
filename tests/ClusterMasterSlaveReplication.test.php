ClusterFullReplication.test.php<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\Api;
use Phpfastcache\CacheManager;
use Phpfastcache\Cluster\AggregatorInterface;
use Phpfastcache\Cluster\ClusterAggregator;
use Phpfastcache\Cluster\ItemAbstract;
use Phpfastcache\Core\Pool\AggregablePoolInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheRootException;
use Phpfastcache\Helper\TestHelper;
use Phpfastcache\Drivers\Fakefiles\Config;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mock/Autoload.php';
$testHelper = new TestHelper('Master/Slave Replication Cluster');

CacheManager::addCustomDriver('Failfiles', \Phpfastcache\Drivers\Failfiles\Driver::class);
$clusterAggregator = new ClusterAggregator('test_10');
$unwantedPool = CacheManager::getInstance('Redis');
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Failfiles'));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Sqlite'));
$clusterAggregator->aggregateDriver($unwantedPool);

try{
    $cluster = $clusterAggregator->getCluster(AggregatorInterface::STRATEGY_MASTER_SLAVE);
    $testHelper->printFailText('The Master/Slave cluster did not thrown an exception with more than 2 pools aggregated.');
}catch(PhpfastcacheInvalidArgumentException $e){
    $testHelper->printPassText('The Master/Slave cluster thrown an exception with more than 2 pools aggregated.');
}
$clusterAggregator->disaggregateDriver($unwantedPool);
$cluster = $clusterAggregator->getCluster(AggregatorInterface::STRATEGY_MASTER_SLAVE);

$testPasses = false;
$cluster->getEventManager()->onCacheReplicationSlaveFallback(static function(ExtendedCacheItemPoolInterface $pool, string $actionName) use (&$testPasses){
    if($actionName === 'getItem'){
        $testPasses = true;
    }
});
$cacheItem = $cluster->getItem('test-test');

if($testPasses && $cacheItem instanceof ItemAbstract){
    $testHelper->printPassText('The Master/Slave cluster successfully switched to slave cluster after backend I/O error.');
}else{
    $testHelper->printFailText('The Master/Slave cluster failed to switch to slave cluster after backend I/O error.');
}
unset($unwantedPool, $cacheItem, $testPasses, $cluster, $clusterAggregator);
CacheManager::clearInstances();

$clusterAggregator = new ClusterAggregator('test_20');
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Redis'));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Files'));
$cluster = $clusterAggregator->getCluster(AggregatorInterface::STRATEGY_MASTER_SLAVE);
$cluster->clear();
$cacheKey = 'cache_' .  \bin2hex(\random_bytes(12));
$cacheValue = 'cache_' .  \random_int(1000, 999999);
$cacheItem = $cluster->getItem($cacheKey);

$cacheItem->set($cacheValue);
$cacheItem->expiresAfter(600);
if($cluster->save($cacheItem)){
    $testHelper->printPassText('The Master/Slave cluster successfully saved an item.');
}else{
    $testHelper->printFailText('The Master/Slave cluster failed to save an item.');
}
unset($clusterAggregator, $cluster, $cacheItem);
CacheManager::clearInstances();

$clusterAggregator = new ClusterAggregator('test_20');
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Redis'));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Files'));
$cluster = $clusterAggregator->getCluster(AggregatorInterface::STRATEGY_MASTER_SLAVE);

$cacheKey = 'cache_' .  \bin2hex(\random_bytes(12));
$cacheItem = $cluster->getItem($cacheKey);

if($cacheItem->get() === $cacheValue){
    $testHelper->printPassText('The Master/Slave cluster successfully retrieved the expected value.');
}else{
    $testHelper->printPassText('The Master/Slave cluster failed to retrieve the expected value.');
}

$testHelper->terminateTest();
