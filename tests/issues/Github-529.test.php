<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$status = 0;

/**
 * @param $obj
 * @param $prop
 * @return mixed
 * @throws \ReflectionException
 */
function accessInaccessibleMember($obj, $prop) {
    $reflection = new \ReflectionClass($obj);
    $property = $reflection->getProperty($prop);
    $property->setAccessible(true);
    return $property->getValue($obj);
}

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

$cacheInstance->save($item);
$cacheInstance->save($item2);
$cacheInstance->detachAllItems();
unset($item, $item2);

/**
 * Destroy the populated items
 */
$cacheInstance->deleteItemsByTag('tag-all');

/**
 * First test memory, as we will write the item inside in the second test
 */
$itemInstances = accessInaccessibleMember($cacheInstance, 'itemInstances');
if (isset($itemInstances[$cacheInstance::DRIVER_TAGS_KEY_PREFIX . 'tag-all'])) {
    echo '[FAIL] The internal cache item tag is still stored in memory' . PHP_EOL;
    $status = 255;
} else {
    echo '[PASS] The internal cache item tag is no longer stored in memory' . PHP_EOL;
}

/**
 * Then test disk to see if the item is still there
 */
if ($cacheInstance->getItem($cacheInstance::DRIVER_TAGS_KEY_PREFIX . 'tag-all')->isHit()) {
    echo '[FAIL] The internal cache item tag is still stored on disk' . PHP_EOL;
    $status = 255;
} else {
    echo '[PASS] The internal cache item tag is no longer stored on disk' . PHP_EOL;
}

exit($status);