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
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Tests\Helper\TestHelper;
use Psr\Cache\CacheItemPoolInterface;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Unsupported key characters');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$driverInstance = CacheManager::getInstance($defaultDriver);

try {
    $driverInstance->getItem('test{test');
    $testHelper->assertFail('1/4 An unsupported key character did not get caught by regular expression');
} catch (PhpfastcacheInvalidArgumentException $e) {
    $testHelper->assertPass('1/4 An unsupported key character has been caught by regular expression');
}

try {
    $driverInstance->getItem(':testtest');
    $testHelper->assertFail('2/4 An unsupported key character did not get caught by regular expression');
} catch (PhpfastcacheInvalidArgumentException $e) {
    $testHelper->assertPass('2/4 An unsupported key character has been caught by regular expression');
}

try {
    $driverInstance->getItem('testtest}');
    $testHelper->assertFail('3/4 An unsupported key character did not get caught by regular expression');
} catch (PhpfastcacheInvalidArgumentException $e) {
    $testHelper->assertPass('3/4 An unsupported key character has been caught by regular expression');
}

try {
    $driverInstance->getItem('testtest');
    $testHelper->assertPass('4/4 No exception caught while trying with a key without unsupported character');
} catch (PhpfastcacheInvalidArgumentException $e) {
    $testHelper->assertFail('4/4 An exception has been caught while trying with a key without unsupported character');
}

$testHelper->terminateTest();
