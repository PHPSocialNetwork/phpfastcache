<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
use phpFastCache\CacheManager;

chdir(__DIR__);
require_once __DIR__ . '/../../src/autoload.php';

$status = 0;
echo "Testing Github issue #373 - Files driver issue after clearing cache\n";

CacheManager::setDefaultConfig(['path' => __DIR__ . '/../../cache']);
$cacheInstance = CacheManager::getInstance('Files');

$key = 'test';
$cacheItem = $cacheInstance->getItem($key);
$cacheItem->set('value');

$cacheInstance->save($cacheItem);
$cacheInstance->deleteItem($key);
$cacheInstance->clear();

try {
    $has = $cacheInstance->hasItem($key);
    echo "[PASS] No error thrown while trying to test if an item exists after clearing\n";
} catch (Exception $e) {
    $status = 255;
    echo "[FAIL] An error has been thrown while trying to test if an item exists after clearing\n";
}

exit($status);