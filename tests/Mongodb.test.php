<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Mongodb driver');

try{
    $cacheInstance = CacheManager::getInstance('Mongodb', [
      'databaseName' => 'pfc_test',
      'username' => 'travis',
      'password' => 'test',
    ]);

    $cacheKey = str_shuffle(uniqid('pfc', true));
    $cacheValue = str_shuffle(uniqid('pfc', true));

    try{
        $item = $cacheInstance->getItem($cacheKey);
        $item->set($cacheValue)->expiresAfter(300);
        $cacheInstance->save($item);
        $testHelper->printPassText('Successfully saved a new cache item into Mongodb server');
    }catch(phpFastCacheDriverException $e){
        $testHelper->printFailText('Failed to save a new cache item into Mongodb server with exception: ' . $e->getMessage());
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
        $testHelper->printFailText('Failed to save a new cache item into Mongodb server with exception: ' . $e->getMessage());
    }

    try{
        unset($item);
        $cacheInstance->detachAllItems();
        $cacheInstance->clear();
        $item = $cacheInstance->getItem($cacheKey);

        if(!$item->isHit()){
            $testHelper->printPassText('Successfully cleared the Mongodb server, no cache item found');
        }else{
            $testHelper->printFailText('Failed to clear the Mongodb server, a cache item has been found');
        }
    }catch(phpFastCacheDriverException $e){
        $testHelper->printFailText('Failed to clear the Mongodb server with exception: ' . $e->getMessage());
    }

    try{
        $item = $cacheInstance->getItem($cacheKey);
        $item->set($cacheValue)->expiresAfter(300);
        $cacheInstance->save($item);

        if($cacheInstance->deleteItem($item->getKey())){
            $testHelper->printPassText('Deleter successfully removed the item from cache');
        }else{
            $testHelper->printFailText('Deleter failed to remove the item from cache');
        }
    }catch(phpFastCacheDriverException $e){
        $testHelper->printFailText('Failed to remove a cache item from Mongodb server with exception: ' . $e->getMessage());
    }
}catch (phpFastCacheDriverCheckException $e){
    // Must be HHVM, right ?
    $testHelper->printSkipText('Ignored test since Mongodb driver since to be unavailable at the moment: ' . $e->getMessage());
}

$testHelper->terminateTest();
