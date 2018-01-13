<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;


chdir(__DIR__);
require_once __DIR__ . '/../../src/autoload.php';
$defaultTTl = 60 * 60 * 24 * 31;
$cacheInstance = CacheManager::getInstance('Sqlite', [
  'defaultTtl' => $defaultTTl
]);
$status = 0;

/**
 * Clear the cache to avoid
 * unexpected results
 */
$cacheInstance->clear();

$cacheKey = uniqid('ck', true);
$string = uniqid('pfc', true);

/**
 * Setup the cache item
 */
$cacheItem = $cacheInstance->getItem($cacheKey);
$cacheItem->set($string);
$cacheInstance->save($cacheItem);
$now = time();

/**
 * Delete memory references
 * to be sure that the values
 * come from the cache itself
 */
unset($cacheItem);
$cacheInstance->detachAllItems();
$cacheItem = $cacheInstance->getItem($cacheKey);

if($cacheItem->getTtl() === $defaultTTl){
    echo '[PASS] The cache Item TTL matches the default TTL after 30 days.' . PHP_EOL;
}else{
    echo '[FAIL] The cache Item TTL des not matches the default TTL after 30 days, got the following value: ' . $cacheItem->getTtl() . PHP_EOL;
    $status = 255;
}

exit($status);