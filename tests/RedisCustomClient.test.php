<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Helper\TestHelper;
use Phpfastcache\Drivers\Redis\Config as RedisConfig;
use Redis as RedisClient;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Redis custom client');

try{
    $redisClient = new RedisClient();
    $redisClient->connect('127.0.0.1', 6379, 5);
    $redisClient->select(0);
    $cacheInstance = CacheManager::getInstance('Redis', (new RedisConfig())->setRedisClient($redisClient));
    $cacheKey = 'redisCustomClient';
    $cacheItem = $cacheInstance->getItem($cacheKey);
    $cacheItem->set(1337);
    $cacheInstance->save($cacheItem);
    $cacheInstance->detachAllItems();
    unset($cacheItem);
    if($cacheInstance->getItem($cacheKey)->get() === 1337){
        $testHelper->printPassText('Successfully written and read data from outside Redis client');
    }else{
        $testHelper->printFailText('Error writing or reading data from outside Redis client');
    }
}catch (\RedisException $e){
    $testHelper->printFailText('A Redis exception occurred: ' . $e->getMessage());
}

$testHelper->terminateTest();