<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Helper\TestHelper;
use Phpfastcache\Drivers\Predis\Config as PredisConfig;
use Predis\Client as PredisClient;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Predis custom client');

try{
    $predisClient = new PredisClient([
      'host' => '127.0.0.1',
      'port' =>  6379,
      'password' => null,
      'database' => 0,
    ]);
    $predisClient->connect();

    $cacheInstance = CacheManager::getInstance('Predis', (new PredisConfig())->setPredisClient($predisClient));
    $cacheKey = 'predisCustomClient';
    $cacheItem = $cacheInstance->getItem($cacheKey);
    $cacheItem->set(1337);
    $cacheInstance->save($cacheItem);
    $cacheInstance->detachAllItems();
    unset($cacheItem);
    if($cacheInstance->getItem($cacheKey)->get() === 1337){
        $testHelper->printPassText('Successfully written and read data from outside Predis client');
    }else{
        $testHelper->printFailText('Error writing or reading data from outside Predis client');
    }
}catch (\RedisException $e){
    $testHelper->printFailText('A Predis exception occurred: ' . $e->getMessage());
}

$testHelper->terminateTest();