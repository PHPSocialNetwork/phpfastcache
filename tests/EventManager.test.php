<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
use phpFastCache\CacheManager;
use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\EventManager;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';

$status = 0;
echo "Testing EventManager\n";

EventManager::getInstance()->onCacheSaveItem(function(ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item){
    if($item->get() === 1000){
        $item->increment(337);
    }
});

$cacheInstance = CacheManager::getInstance('Files');
$cacheKey = 'testItem';

$item = $cacheInstance->getItem($cacheKey);
$item->set(1000)->expiresAfter(60);
$cacheInstance->save($item);


if($cacheInstance->getItem($cacheKey)->get() === 1337){
    echo "[PASS] The dispatched event executed the custom callback to alter to item.\n";
}else{
    echo "[FAIL] The dispatched event did not worked well, the expected value '1337', got '" . (int) $cacheInstance->getItem($cacheKey)->get() . "'\n";
    $status = 1;
}

exit($status);