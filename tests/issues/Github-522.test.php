<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #522 - Predis returns wrong type hints');
// Hide php Redis extension notice by using a little @
$cacheInstance = @CacheManager::getInstance('Predis');
$stringObject = new stdClass;
$stringObject->test = '';

try {

    /**
     * Clear Predis cache
     */
    $cacheInstance->clear();
    for($i = 0; $i < 1000; $i++)
    {
        $stringObject->test .= md5(uniqid('pfc', true));
    }
    $stringObject->test  = str_shuffle($stringObject->test );

    $item1 = $cacheInstance->getItem('item1');
    $item2 = $cacheInstance->getItem('item2');
    $item3 = $cacheInstance->getItem('item3');

    $item1->isHit() ?: $item1->set(clone $stringObject)->expiresAfter(20);
    $item2->isHit() ?: $item2->set(clone $stringObject)->expiresAfter(20);
    $item3->isHit() ?: $item3->set(clone $stringObject)->expiresAfter(20);

    $item1->isHit() ?: $cacheInstance->save($item1);
    $item2->isHit() ?: $cacheInstance->save($item2);
    $item3->isHit() ?: $cacheInstance->save($item3);

    $cacheInstance->deleteItem($item2->getKey());
    $cacheInstance->deleteItem($item3->getKey());
    $cacheInstance->detachAllItems();
    unset($item1, $item2, $item3);

    if($cacheInstance->getItem('item1')->isHit() && $cacheInstance->getItem('item1')->get()->test === $stringObject->test){
        $testHelper->printPassText('The cache item "item1" returned the expected value.');
    }else{
        $testHelper->printFailText('The cache item "item1" returned an expected value: ' . gettype($stringObject));
    }

    if(!$cacheInstance->getItem('item2')->isHit() && !$cacheInstance->getItem('item2')->isHit()){
        $testHelper->printPassText('The cache items "item2, item3" are not stored in cache as expected.');
    }else{
        $testHelper->printFailText('The cache items "item2, item3" are unexpectedly stored in cache.');
    }

    $cacheInstance->clear();

    if(!$cacheInstance->getItem('item1')->isHit() && $cacheInstance->getItem('item1')->get() === null){
        $testHelper->printPassText('After a cache clear the cache item "item1" is not stored in cache as expected.');
    }else{
        $testHelper->printFailText('After a cache clear the cache item "item1" is still unexpectedly stored in cache.');
    }
} catch (\Throwable $e) {
    $testHelper->printFailText('The test did not ended well, an error occurred: ' . $e->getMessage());
}

$testHelper->terminateTest();