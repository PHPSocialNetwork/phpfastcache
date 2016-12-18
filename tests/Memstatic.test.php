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
echo "Testing Memstatic driver\n";

$cacheInstance = CacheManager::getInstance('Memstatic');
$cacheKey = 'testItem';
$randomStr = str_shuffle(sha1(uniqid('pfc', true) . mt_rand(100, 10000)));
echo "Random-generated cache value for key '{$cacheKey}': {$randomStr}\n";


$item = $cacheInstance->getItem($cacheKey);
$item->set($randomStr)->expiresAfter(60);
$cacheInstance->save($item);
$cacheInstance->detachAllItems();
unset($item);

$item = $cacheInstance->getItem($cacheKey);

$cacheResult = $cacheInstance->getItem($cacheKey)->get();

if($cacheResult === $randomStr){
  echo "[PASS] The cache key value match, got expected value '{$cacheResult}'\n";
}else{
  echo "[FAIL] The cache key value match expected value '{$randomStr}', got '{$cacheResult}'\n";
  $status = 1;
}
echo "Clearing the whole cache to test item cleaning...\n";
$cacheInstance->clear();
$cacheResult = ($cacheInstance->getItem($cacheKey)->isHit() === false && $cacheInstance->getItem($cacheKey)->get() === null);

if($cacheResult === true){
  echo "[PASS] The cache item is null as expected\n";
}else{
  echo "[FAIL] The cache is not null'\n";
  $status = 1;
}

exit($status);