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
use Psr\Cache\CacheItemPoolInterface;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('PSR6 Interface Implements');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

/**
 * Testing memcached as it is declared in .travis.yml
 */
$driverInstance = CacheManager::getInstance($defaultDriver);

if (!is_object($driverInstance)) {
    $testHelper->assertFail('CacheManager::getInstance() returned an invalid variable type:' . gettype($driverInstance));
} elseif (!($driverInstance instanceof CacheItemPoolInterface)) {
    $testHelper->assertFail('CacheManager::getInstance() returned an invalid class:' . get_class($driverInstance));
} else {
    $testHelper->assertPass('CacheManager::getInstance() returned a valid CacheItemPoolInterface object: ' . get_class($driverInstance));
}

$testHelper->terminateTest();
