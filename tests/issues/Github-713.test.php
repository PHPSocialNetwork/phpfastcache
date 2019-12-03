<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Files\Config as FilesConfig;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #713 - Api Method "deleteItemsByTagsAll()" removes unrelated items');
$cacheInstance = CacheManager::getInstance('Files', new FilesConfig(['securityKey' => 'test-713']));
$cacheInstance->clear();

$item1 = $cacheInstance->getItem('item1');
$item2 = $cacheInstance->getItem('item2');

$item1->addTags(['shared', 'custom1']);
$item1->set(1337);

$item2->addTags(['shared']);
$item2->set(1337);

$cacheInstance->saveMultiple($item1, $item2);
$cacheInstance->detachAllItems();
unset($item1, $item2);
$cacheInstance->deleteItemsByTagsAll(['shared']);

$item1 = $cacheInstance->getItem('item1');
$item2 = $cacheInstance->getItem('item2');

if ($item1->isHit()) {
    $testHelper->printPassText('Item #1 is still in cache as expected.');
} else {
    $testHelper->printFailText('Item #1 is no longer in cache and so has been unexpectedly removed.');
}

$testHelper->terminateTest();