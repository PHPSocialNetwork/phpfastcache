<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Files\Config as FilesConfig;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #893 - getItemsByTag() - empty after one item has expired');

$config = new FilesConfig();
$testHelper->preConfigure($config);
$cacheInstance = CacheManager::getInstance('Files', $config);
$cacheInstance->clear();

$testHelper->printInfoText('Creating cache item "key_1" & "key_2"...');

$CachedString1 = $cacheInstance->getItem("key_1");
if (is_null($CachedString1->get())) {
    $CachedString1->set("data1")->expiresAfter(10);
    $CachedString1->addTag("query");
    $cacheInstance->save($CachedString1);
}


$CachedString2 = $cacheInstance->getItem("key_2");
if (is_null($CachedString2->get())) {
    $CachedString2->set("data2")->expiresAfter(5);
    $CachedString2->addTag("query");
    $cacheInstance->save($CachedString2);
}

$CachedString3 = $cacheInstance->getItem("key_3");
if (is_null($CachedString3->get())) {
    $CachedString3->set("data3")->expiresAfter(4);
    $CachedString3->addTag("query");
    $cacheInstance->save($CachedString3);
}

$cacheInstance->detachAllItems();
$testHelper->printInfoText('Items created and saved, sleeping 6 secondes to force "key_1" to expire');
sleep(6);

$cacheItems = $cacheInstance->getItemsByTag("query");

if(count($cacheItems) === 1 && isset($cacheItems['key_1']) && $cacheItems['key_1']->get() === 'data1') {
    $testHelper->assertPass('getItemsByTag() has returned only cache item "key_1"');
} else {
    $testHelper->assertFail(sprintf('getItemsByTag() returned unknown results: %d item(s)...', \count($cacheItems)));
}

$testHelper->terminateTest();
