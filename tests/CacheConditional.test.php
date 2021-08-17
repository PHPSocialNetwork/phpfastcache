<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Helper\CacheConditionalHelper as CacheConditional;
use Phpfastcache\Tests\Helper\TestHelper;
use Psr\Cache\CacheItemPoolInterface;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Cache Promise');
$defaultDriver = (!empty($argv[ 1 ]) ? ucfirst($argv[ 1 ]) : 'Files');
$cacheInstance = CacheManager::getInstance($defaultDriver);
$cacheKey = 'cacheKey';
$RandomCacheValue = str_shuffle(uniqid('pfc', true));


/**
 * Missing cache item test
 */
$cacheValue = (new CacheConditional($cacheInstance))->get($cacheKey, static function() use ($cacheKey, $testHelper, $RandomCacheValue){
    if(func_get_arg(0) instanceof ExtendedCacheItemInterface){
        $testHelper->assertPass('The callback has been received the cache item as a parameter (introduced in 8.0.6).');
    }else{
        $testHelper->assertFail('The callback has not received the cache item as a parameter (introduced in 8.0.6).');
    }
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
    $testHelper->assertPass(sprintf('The cache promise successfully returned expected value "%s".', $cacheValue));
}else{
    $testHelper->assertFail(sprintf('The cache promise returned an unexpected value "%s".', $cacheValue));
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

$cacheValue = (new CacheConditional($cacheInstance))->get($cacheKey, static function() use ($cacheKey, $testHelper, $RandomCacheValue){
    /**
     * No parameter are passed
     * to this closure
     */
    $testHelper->assertFail('Unexpected closure call.');
    return $RandomCacheValue . '-1337';
});

if($cacheValue === $RandomCacheValue){
    $testHelper->assertPass(sprintf('The cache promise successfully returned expected value "%s".', $cacheValue));
}else{
    $testHelper->assertFail(sprintf('The cache promise returned an unexpected value "%s".', $cacheValue));
}

$cacheInstance->clear();

/**
 * Test TTL
 * @since 7.0
 */
$ttl = 5;
$RandomCacheValue = str_shuffle(uniqid('pfc', true));
$cacheInstance->detachAllItems();
unset($cacheItem);

$cacheValue = (new CacheConditional($cacheInstance))->get($cacheKey, static function() use ($cacheKey, $testHelper, $RandomCacheValue){
    return $RandomCacheValue;
}, $ttl);

$testHelper->printText(sprintf('Sleeping for %d seconds...', $ttl));
sleep($ttl + 1);
$cacheInstance->detachAllItems();
$cacheItem = $cacheInstance->getItem($cacheKey);

if(!$cacheItem->isHit()){
    $testHelper->assertPass(sprintf('The cache promise ttl successfully expired the cache after %d seconds', $ttl));
}else{
    $testHelper->assertFail(sprintf('The cache promise ttl does not expired the cache after %d seconds', $ttl));
}

$cacheInstance->clear();
$testHelper->terminateTest();
