<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Helper\CacheConditionalHelper as CacheConditional;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mock/autoload.php';
$testHelper = new TestHelper('Custom namespaces');

if(!class_exists(Phpfastcache\DriverTest\Files2\Item::class)
  || !class_exists(Phpfastcache\DriverTest\Files2\Driver::class)
  || !class_exists(Phpfastcache\DriverTest\Files2\Config::class)
){
    $testHelper->printFailText('The php classes of driver "Files2" does not exists');
    $testHelper->terminateTest();
}else{
    $testHelper->printPassText('The php classes of driver "Files2" were found');
}

$testHelper->printNoteText('Please note that as of the V7 custom namespace are deprecated, use override feature instead.');
CacheManager::setNamespacePath(Phpfastcache\DriverTest::class);
$cacheInstance = CacheManager::getInstance('Files2');
$cacheKey = 'cacheKey';
$RandomCacheValue = str_shuffle(uniqid('pfc', true));

/**
 * Existing cache item test
 */
$cacheItem = $cacheInstance->getItem($cacheKey);
$RandomCacheValue = str_shuffle(uniqid('pfc', true));
$cacheItem->set($RandomCacheValue);
$cacheInstance->save($cacheItem);

/**
 * Remove objects references
 */
$cacheInstance->detachAllItems();
unset($cacheItem);

$cacheValue = (new CacheConditional($cacheInstance))->get($cacheKey, function() use ($cacheKey, $testHelper, $RandomCacheValue){
    /**
     * No parameter are passed
     * to this closure
     */
    $testHelper->printFailText('Unexpected closure call.');
    return $RandomCacheValue . '-1337';
});

if($cacheValue === $RandomCacheValue){
    $testHelper->printPassText(sprintf('The cache promise successfully returned expected value "%s".', $cacheValue));
}else{
    $testHelper->printFailText(sprintf('The cache promise returned an unexpected value "%s".', $cacheValue));
}

$cacheInstance->clear();
$testHelper->terminateTest();