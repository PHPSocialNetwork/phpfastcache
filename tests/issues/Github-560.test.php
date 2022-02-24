<?php

declare(strict_types=1);

/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #560 - Expiration date bug with sqlite driver');
$defaultTTl = 60 * 60 * 24 * 31;
$cacheInstance = CacheManager::getInstance('Sqlite', new ConfigurationOption([
    'defaultTtl' => $defaultTTl,
]));

/*
 * Clear the cache to avoid
 * unexpected results
 */
$cacheInstance->clear();

$cacheKey = uniqid('ck', true);
$string = uniqid('pfc', true);
$testHelper->printText('Preparing test item...');

/**
 * Setup the cache item
 */
$cacheItem = $cacheInstance->getItem($cacheKey);
$cacheItem->set($string);
$cacheInstance->save($cacheItem);

/*
 * Delete memory references
 * to be sure that the values
 * come from the cache itself
 */
unset($cacheItem);
$cacheInstance->detachAllItems();
$cacheItem = $cacheInstance->getItem($cacheKey);

/*
 * Round up to the nearest 10 to avoid a potential issue
 * due to the time spend to write the cache on disk that will
 * loss 1 second to the cache ttl :/
 */
if ((int) ceil($cacheItem->getTtl() / 10) * 10 === $defaultTTl) {
    $testHelper->assertPass('The cache Item TTL matches the default TTL after 30 days.');
} else {
    $testHelper->assertFail('The cache Item TTL des not matches the default TTL after 30 days, got the following value: ' . ceil($cacheItem->getTtl() / 10) * 10);
}

$testHelper->terminateTest();
