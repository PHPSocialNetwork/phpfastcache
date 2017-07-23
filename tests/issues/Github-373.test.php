<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../src/autoload.php';
$testHelper = new TestHelper('Github issue #373 - Files driver issue after clearing cache');
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
    $testHelper->printPassText('No error thrown while trying to test if an item exists after clearing');
} catch (Exception $e) {
    $testHelper->printFailText('An error has been thrown while trying to test if an item exists after clearing');
}

$testHelper->terminateTest();