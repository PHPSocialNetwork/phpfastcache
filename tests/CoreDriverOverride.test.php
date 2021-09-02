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
use Phpfastcache\DriverTest\Files2\Config;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\CacheContract as CacheConditional;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mock/Autoload.php';
$testHelper = new TestHelper('Core driver override');

if (!class_exists(Phpfastcache\DriverTest\Files2\Item::class)
  || !class_exists(Phpfastcache\DriverTest\Files2\Driver::class)
  || !class_exists(Phpfastcache\DriverTest\Files2\Config::class)
) {
    $testHelper->assertFail('The php classes of driver "Files2" does not exists');
    $testHelper->terminateTest();
} else {
    $testHelper->assertPass('The php classes of driver "Files2" were found');
}

try {
    CacheManager::addCoreDriverOverride('Files2', \Phpfastcache\DriverTest\Files2\Driver::class);
    $testHelper->assertFail('No exception thrown while trying to override an non-existing driver');
} catch (PhpfastcacheLogicException $e) {
    $testHelper->assertPass('An exception has been thrown while trying to override an non-existing driver');
}

try {
    CacheManager::addCoreDriverOverride('', \Phpfastcache\DriverTest\Files2\Driver::class);
    $testHelper->assertFail('No exception thrown while trying to override an empty driver');
} catch (PhpfastcacheInvalidArgumentException $e) {
    $testHelper->assertPass('An exception has been thrown while trying to override an empty driver');
}

CacheManager::addCoreDriverOverride('Files', \Phpfastcache\DriverTest\Files2\Driver::class);

$cacheInstance = CacheManager::getInstance('Files', new Config(['customOption' => true]));
$cacheKey = 'cacheKey';
$RandomCacheValue = str_shuffle(uniqid('pfc', true));

if($cacheInstance instanceof \Phpfastcache\DriverTest\Files2\Driver){
    $testHelper->assertPass('The cache instance is effectively an instance of an override class');
}else{
    $testHelper->assertFail('The cache instance is not an instance of an override class');
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

$cacheValue = (new CacheConditional($cacheInstance))->get($cacheKey, function () use ($cacheKey, $testHelper, $RandomCacheValue) {
    /**
     * No parameter are passed
     * to this closure
     */
    $testHelper->assertFail('Unexpected closure call.');
    return $RandomCacheValue . '-1337';
});

if ($cacheValue === $RandomCacheValue) {
    $testHelper->assertPass(sprintf('The cache promise successfully returned expected value "%s".', $cacheValue));
} else {
    $testHelper->assertFail(sprintf('The cache promise returned an unexpected value "%s".', $cacheValue));
}

CacheManager::removeCoreDriverOverride('Files');
$cacheInstance = CacheManager::getInstance('Files');

if($cacheInstance instanceof \Phpfastcache\DriverTest\Files2\Driver){
    $testHelper->assertFail('The cache instance is still an instance of an override class');
}else{
    $testHelper->assertPass('The cache instance is no longer an instance of an override class');
}

$cacheInstance->clear();
$testHelper->terminateTest();
