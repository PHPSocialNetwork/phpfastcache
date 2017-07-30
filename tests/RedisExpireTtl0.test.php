<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('(P)Redis Expire TTL to 0');
$cacheInstance = CacheManager::getInstance('Redis', []);
$cacheKey = 'cacheKey';
$RandomCacheValue = str_shuffle(uniqid('pfc', true));
$loops = 10;

$testHelper->printText('See https://redis.io/commands/setex');
$testHelper->printText('See https://redis.io/commands/expire');
$testHelper->printNewLine();

for ($i = 0; $i <= $loops; $i++)
{
    $cacheItem = $cacheInstance->getItem("{$cacheKey}-{$i}");
    $cacheItem->set($RandomCacheValue)
      ->expiresAt(new DateTime());

    $cacheInstance->saveDeferred($cacheItem);
}

try{
    $cacheInstance->commit();
    $testHelper->printPassText('The COMMIT operation has finished successfully');
}catch (Predis\Response\ServerException $e){
    if(strpos($e->getMessage(), 'setex')){
        $testHelper->printFailText('The COMMIT operation has failed due to to an invalid time detection.');
    }else{
        $testHelper->printFailText('The COMMIT operation has failed due to to an unexpected error: ' . $e->getMessage());
    }
}
$cacheInstance->detachAllItems();

$testHelper->printText('Sleeping a second...');


sleep(1);

for ($i = 0; $i <= $loops; $i++)
{
    $cacheItem =  $cacheInstance->getItem("{$cacheKey}-{$i}");

    if($cacheItem->isHit()){
        $testHelper->printFailText(sprintf('The cache item "%s" is considered as HIT with the following value: %s', $cacheItem->getKey(), $cacheItem->get()));
    }else{
        $testHelper->printPassText(sprintf('The cache item "%s" is not considered as HIT.',  $cacheItem->getKey()));
    }
}

$cacheInstance->clear();

$testHelper->terminateTest();