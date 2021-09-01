<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #529 - Memory leak caused by item tags');
// Hide php Redis extension notice by using a little @
$cacheInstance = CacheManager::getInstance('Files');
$string = uniqid('pfc', true);

/**
 * Populate the cache with some data
 */
[$item, $item2] = array_values($cacheInstance->getItems(['item1', 'item2']));

$item->set($string)
  ->addTags(['tag-all', 'tag1'])
  ->expiresAfter(3600);

$item2->set($string)
  ->addTags(['tag-all', 'tag2'])
  ->expiresAfter(3600);

$cacheInstance->saveMultiple(...[$item, $item2]);
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
    $testHelper->assertFail('The internal cache item tag is still stored in memory');
} else {
    $testHelper->assertPass('The internal cache item tag is no longer stored in memory');
}

/**
 * Then test disk to see if the item is still there
 */
if ($cacheInstance->getItem($cacheInstance::DRIVER_TAGS_KEY_PREFIX . 'tag-all')->isHit()) {
    $testHelper->assertFail('The internal cache item tag is still stored on disk');
} else {
    $testHelper->assertPass('The internal cache item tag is no longer stored on disk');
}

$testHelper->terminateTest();
