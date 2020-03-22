<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Helper\TestHelper;
use Phpfastcache\Drivers\Redis\Config as RedisConfig;
use Redis as RedisClient;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Redis custom client');

try{
    if(!class_exists(RedisClient::class)){
        throw new PhpfastcacheDriverCheckException('Unable to test Redis client because the extension seems to be missing');
    }
    $redisClient = new RedisClient();
    $redisClient->connect('127.0.0.1', 6379, 5);
    $redisClient->select(0);
    $cacheInstance = CacheManager::getInstance('Redis', (new RedisConfig())->setRedisClient($redisClient));
    $testHelper->runCRUDTests($cacheInstance);
}catch (\RedisException $e){
    $testHelper->printFailText('A Redis exception occurred: ' . $e->getMessage());
}

$testHelper->terminateTest();