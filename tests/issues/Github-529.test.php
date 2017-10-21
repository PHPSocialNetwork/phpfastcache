<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #529 - Memory leak caused by item tags');
// Hide php Redis extension notice by using a little @
$cacheInstance = CacheManager::getInstance('Files');
$string = uniqid('pfc', true);

/**
 * Populate the cache with some data
 */
list($item, $item2) = array_values($cacheInstance->getItems(['item1', 'item2']));

$item->set($string)
  ->addTags(['tag-all', 'tag1'])
  ->expiresAfter(3600);

$item2->set($string)
  ->addTags(['tag-all', 'tag2'])
  ->expiresAfter(3600);

$cacheInstance->saveMultiple([$item, $item2]);
$cacheInstance->detachAllItems();
unset($item, $item2);

/**
 * Destroy the populated items
 */
$cacheInstance->deleteItemsByTag('tag-all');

/**
 * First test memory, as we will write the item inside in the second test
 */
$itemInstances = $testHelper->accessInaccessibleMember($cacheInstance, 'itemInstances');
if (isset($itemInstances[$cacheInstance::DRIVER_TAGS_KEY_PREFIX . 'tag-all'])) {
    $testHelper->printFailText('The internal cache item tag is still stored in memory');
} else {
    $testHelper->printPassText('The internal cache item tag is no longer stored in memory');
}

/**
 * Then test disk to see if the item is still there
 */
if ($cacheInstance->getItem($cacheInstance::DRIVER_TAGS_KEY_PREFIX . 'tag-all')->isHit()) {
    $testHelper->printFailText('The internal cache item tag is still stored on disk');
} else {
    $testHelper->printPassText('The internal cache item tag is no longer stored on disk');
}

$testHelper->terminateTest();