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
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\CacheContract;
use Phpfastcache\Tests\Helper\TestHelper;
use Psr\Cache\CacheItemInterface;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Cache Contract');
$defaultDriver = (!empty($argv[ 1 ]) ? ucfirst($argv[ 1 ]) : 'Files');
$cacheInstance = CacheManager::getInstance($defaultDriver);
$cacheKey = 'cacheKey';
$RandomCacheValue = str_shuffle(uniqid('pfc', true));
$cacheInstance->clear();

/**
 * Missing cache item test
 */
$cacheValue = (new CacheContract($cacheInstance))->get($cacheKey, static function() use ($cacheKey, $testHelper, $RandomCacheValue){
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
    $testHelper->assertPass(sprintf('The cache contract successfully returned expected value "%s".', $cacheValue));
}else{
    $testHelper->assertFail(sprintf('The cache contract returned an unexpected value "%s".', $cacheValue));
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

$cacheValue = (new CacheContract($cacheInstance))->get($cacheKey, static function() use ($cacheKey, $testHelper, $RandomCacheValue){
    /**
     * No parameter are passed
     * to this closure
     */
    $testHelper->assertFail('Unexpected closure call.');
    return $RandomCacheValue . '-1337';
});

if($cacheValue === $RandomCacheValue){
    $testHelper->assertPass(sprintf('The cache contract successfully returned expected value "%s".', $cacheValue));
}else{
    $testHelper->assertFail(sprintf('The cache contract returned an unexpected value "%s".', $cacheValue));
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

$cacheValue = (new CacheContract($cacheInstance))->get($cacheKey, static fn() => $RandomCacheValue, $ttl);

$testHelper->printText(sprintf('Sleeping for %d seconds...', $ttl));
sleep($ttl + 1);
$cacheInstance->detachAllItems();
$cacheItem = $cacheInstance->getItem($cacheKey);

if(!$cacheItem->isHit()){
    $testHelper->assertPass(sprintf('The cache contract ttl successfully expired the cache after %d seconds', $ttl));
}else{
    $testHelper->assertFail(sprintf('The cache contract ttl does not expired the cache after %d seconds', $ttl));
}

/**
 * Test closure first argument
 * @since 8.0.6
 */
$cacheInstance->clear();
(new CacheContract($cacheInstance))->get($cacheKey, static function() use ($testHelper){
    $args = func_get_args();
    if(isset($args[0]) && $args[0] instanceof CacheItemInterface){
        $testHelper->assertPass('The callback has been received the cache item as the first parameter');
    }else{
        $testHelper->assertFail('The callback did not received the cache item as the first parameter');
    }
});


/**
 * Test callable cache contract syntax via __invoke()
 * @since 8.0.6
 */
try{
    $value = (new CacheContract($cacheInstance))($cacheKey, static function() use ($testHelper){
        $testHelper->assertPass('The CacheContract class is callable via __invoke()');
        return null;
    });
}catch(\Error $e){
    $testHelper->assertFail('The CacheContract class is not callable via __invoke()');
}catch(\Throwable $e){
    $testHelper->assertFail('Got an unknown error: ' . $e->getMessage());
}

$cacheInstance->clear();
$testHelper->terminateTest();
