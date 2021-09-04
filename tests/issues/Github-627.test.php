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
use Phpfastcache\Drivers\Redis\Config as RedisConfig;
use Phpfastcache\Drivers\Predis\Config as PredisConfig;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #627 - Redis/Predis "prefix" option');
$testHelper->mutePhpNotices();
$redisInstance = CacheManager::getInstance('Redis', new RedisConfig(['optPrefix' => uniqid('pfc', true) . '_']));
$predisInstance = CacheManager::getInstance('Predis', new PredisConfig(['optPrefix' => uniqid('pfc', true) . '_']));

$testHelper->printInfoText('Testing Redis 1/2');

/**
 * Clear the cache to avoid
 * unexpected results
 */
$redisInstance->clear();

$cacheKey = uniqid('ck', true);
$string = uniqid('pfc', true);
$testHelper->printText('Preparing test item...');

/**
 * Setup the cache item
 */
$cacheItem = $redisInstance->getItem($cacheKey);
$cacheItem->set($string);
$redisInstance->save($cacheItem);
unset($cacheItem);
$redisInstance->detachAllItems();

if ($redisInstance->getItem($cacheKey)->isHit()) {
    $testHelper->assertPass('The cache item has been found in cache');
} else {
    $testHelper->assertFail('The cache item was not found in cache');
}

$testHelper->printInfoText('Testing Predis 2/2');

/**
 * Clear the cache to avoid
 * unexpected results
 */
$predisInstance->clear();

$cacheKey = uniqid('ck', true);
$string = uniqid('pfc', true);
$testHelper->printText('Preparing test item...');

/**
 * Setup the cache item
 */
$cacheItem = $predisInstance->getItem($cacheKey);
$cacheItem->set($string);
$predisInstance->save($cacheItem);
unset($cacheItem);
$predisInstance->detachAllItems();

if ($predisInstance->getItem($cacheKey)->isHit()) {
    $testHelper->assertPass('The cache item has been found in cache');
} else {
    $testHelper->assertFail('The cache item was not found in cache');
}


$testHelper->terminateTest();
