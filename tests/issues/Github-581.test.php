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
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #581 - Files driver "securityKey" option configuration not working as documented');
$cacheInstance = CacheManager::getInstance('Files');

/*
 * Force the HTTP_HOST in CLI to
 * simulate a Web request
 */
$_SERVER['HTTP_HOST'] = uniqid('test.', true) . '.pfc.net';

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
unset($cacheItem);
$cacheInstance->detachAllItems();

if (str_contains($cacheInstance->getPath(), 'phpfastcache' . \DIRECTORY_SEPARATOR . $_SERVER['HTTP_HOST'])) {
    $testHelper->assertPass('The "securityKey" option in automatic mode writes the HTTP_HOST directory as expected.');
} else {
    $testHelper->assertFail('The "securityKey" option in automatic mode leads to the following path: ' . $cacheInstance->getPath());
}

$testHelper->terminateTest();
