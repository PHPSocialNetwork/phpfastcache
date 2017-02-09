<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Couchdb driver');
$cacheInstance = CacheManager::getInstance('Couchdb');


$cacheKey = str_shuffle(uniqid('pfc', true));
$cacheValue = str_shuffle(uniqid('pfc', true));

try{
    $item = $cacheInstance->getItem($cacheKey);
    $item->set($cacheValue)->expiresAfter(300);
    $cacheInstance->save($item);
    $testHelper->printPassText('Successfully saved a new cache item into Couchdb server');
}catch(phpFastCacheDriverException $e){
    $testHelper->printFailText('Failed to save a new cache item into Couchdb server with exception: ' . $e->getMessage());
}


try{
    unset($item);
    $cacheInstance->detachAllItems();
    $item = $cacheInstance->getItem($cacheKey);

    if($item->get() === $cacheValue){
        $testHelper->printPassText('Getter returned expected value: ' . $cacheValue);
    }else{
        $testHelper->printFailText('Getter returned unexpected value, expecting "' . $cacheValue . '", got "' . $item->get() . '"');
    }
}catch(phpFastCacheDriverException $e){
    $testHelper->printFailText('Failed to save a new cache item into Couchdb server with exception: ' . $e->getMessage());
}

try{
    unset($item);
    $cacheInstance->detachAllItems();
    $cacheInstance->clear();
    $item = $cacheInstance->getItem($cacheKey);

    if(!$item->isHit()){
        $testHelper->printPassText('Successfully cleared the Couchdb server, no cache item found');
    }else{
        $testHelper->printFailText('Failed to clear the Couchdb server, a cache item has been found');
    }
}catch(phpFastCacheDriverException $e){
    $testHelper->printFailText('Failed to clear the Couchdb server with exception: ' . $e->getMessage());
}

$testHelper->terminateTest();
