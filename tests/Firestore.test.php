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
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Drivers\Firestore\Config as FirestoreConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Google Firestore driver');

/**
 * This driver awaits some fixes on Google side related to psr/cache version
 *
 * @see https://github.com/googleapis/google-auth-library-php/pull/364
 * @see https://github.com/googleapis/google-auth-library-php/issues/363
 */



$config = new FirestoreConfig();

try {
    $config->setItemDetailedDate(true);
    $config->setCollection('phpfastcache');
    $cacheInstance = CacheManager::getInstance('Firestore', $config);
    $testHelper->runCRUDTests($cacheInstance);
} catch (PhpfastcacheDriverConnectException $e) {
    $testHelper->assertSkip('Firestore server unavailable: ' . $e->getMessage());
    $testHelper->terminateTest();
}
$testHelper->terminateTest();
