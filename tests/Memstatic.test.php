<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Memstatic driver');
$cacheInstance = CacheManager::getInstance('Memstatic');
$cacheKey = 'testItem';
$randomStr = str_shuffle(sha1(uniqid('pfc', true) . mt_rand(100, 10000)));
$testHelper->printText("Random-generated cache value for key '{$cacheKey}': {$randomStr}");

$item = $cacheInstance->getItem($cacheKey);
$item->set($randomStr)->expiresAfter(60);
$cacheInstance->save($item);
$cacheInstance->detachAllItems();
unset($item);

$item = $cacheInstance->getItem($cacheKey);

$cacheResult = $cacheInstance->getItem($cacheKey)->get();

if ($cacheResult === $randomStr) {
    $testHelper->assertPass("The cache key value match, got expected value '{$cacheResult}'");
} else {
    $testHelper->assertFail("The cache key value match expected value '{$randomStr}', got '{$cacheResult}'");
}
$testHelper->printText('Clearing the whole cache to test item cleaning...');
$cacheInstance->clear();
$cacheResult = ($cacheInstance->getItem($cacheKey)->isHit() === false && $cacheInstance->getItem($cacheKey)->get() === null);

if ($cacheResult === true) {
    $testHelper->assertPass('The cache item is null as expected');
} else {
    $testHelper->assertFail('The cache is not null');
}

$testHelper->terminateTest();
