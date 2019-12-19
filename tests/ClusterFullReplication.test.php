<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\Api;
use Phpfastcache\CacheManager;
use Phpfastcache\Cluster\ClusterAggregator;
use Phpfastcache\Exceptions\PhpfastcacheRootException;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('API class');


$clusterAggregator = new ClusterAggregator('test_20');
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Files'));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Redis'));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Memcache'));

$cluster = $clusterAggregator->getCluster();
$cacheItem = $cluster->getItem('test-test');

/*var_dump($cacheItem->get());
$number = random_int(999, 99999);
$cacheItem->set($number);
$cacheItem->expiresAfter(3605);
$cluster->save($cacheItem);
var_dump($number);*/
/*$cluster->clear();
unset($cacheItem);


$cacheItem = $cluster->getItem('test-test');

var_dump($cacheItem);*/
/**
 * Testing API version
 */
/*try {
    $version = Api::getVersion();
    $testHelper->printPassText(sprintf('Successfully retrieved the API version: %s', $version));
} catch (PhpfastcacheRootException $e) {
    $testHelper->printFailText(sprintf('Failed to retrieve the API version with the following error error: %s', $e->getMessage()));
}*/


$testHelper->terminateTest();
