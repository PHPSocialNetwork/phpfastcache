<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Helper\CacheConditionalHelper as CacheConditional;
use phpFastCache\Helper\TestHelper;
use Psr\Cache\CacheItemPoolInterface;

chdir(__DIR__);
require_once __DIR__ . '/../src/autoload.php';
$testHelper = new TestHelper('Cache Promise');
$defaultDriver = (!empty($argv[ 1 ]) ? ucfirst($argv[ 1 ]) : 'Files');
$cacheInstance = CacheManager::getInstance($defaultDriver, []);
$cacheKey = 'cacheKey';
$RandomCacheValue = str_shuffle(uniqid('pfc', true));


/**
 * Missing cache item test
 */
$cacheValue = (new CacheConditional($cacheInstance))->get($cacheKey, function() use ($cacheKey, $testHelper, $RandomCacheValue){
    /**
     * No parameter are passed
     * to this closure
     */
    $testHelper->printText('Entering in closure as the cache item does not come from the cache backend.');

    /**
     * Here's your database/webservice/etc stuff
     */

    return $RandomCacheValue . '-1337';
});

if($cacheValue === $RandomCacheValue . '-1337'){
    $testHelper->printPassText(sprintf('The cache promise successfully returned expected value "%s".', $cacheValue));
}else{
    $testHelper->printFailText(sprintf('The cache promise returned an unexpected value "%s".', $cacheValue));
}

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