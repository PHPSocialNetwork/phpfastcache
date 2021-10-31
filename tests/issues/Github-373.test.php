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
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #373 - Files driver issue after clearing cache');
CacheManager::setDefaultConfig(new ConfigurationOption(['path' => __DIR__ . '/../../cache']));
$cacheInstance = CacheManager::getInstance('Files');

$key = 'test';
$cacheItem = $cacheInstance->getItem($key);
$cacheItem->set('value');

$cacheInstance->save($cacheItem);
$cacheInstance->deleteItem($key);
$cacheInstance->clear();

try {
    $has = $cacheInstance->hasItem($key);
    $testHelper->assertPass('No error thrown while trying to test if an item exists after clearing');
} catch (Exception $e) {
    $testHelper->assertFail('An error has been thrown while trying to test if an item exists after clearing');
}

$testHelper->terminateTest();
