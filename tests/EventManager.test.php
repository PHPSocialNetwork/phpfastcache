<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\EventManager;
use phpFastCache\Helper\TestHelper;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('EventManager');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

EventManager::getInstance()->onCacheSaveItem(function(ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item){
    if($item->get() === 1000){
        $item->increment(337);
    }
});

$cacheInstance = CacheManager::getInstance($defaultDriver);
$cacheKey = 'testItem';

$item = $cacheInstance->getItem($cacheKey);
$item->set(1000)->expiresAfter(60);
$cacheInstance->save($item);


if($cacheInstance->getItem($cacheKey)->get() === 1337){
    $testHelper->printPassText('The dispatched event executed the custom callback to alter the item');
}else{
    $testHelper->printFailText("The dispatched event is not working properly, the expected value '1337', got '" . (int) $cacheInstance->getItem($cacheKey)->get() . "'");
}

$testHelper->terminateTest();