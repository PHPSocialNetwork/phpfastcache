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
use Phpfastcache\Drivers\Fakefiles\Config;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../mock/Autoload.php';
$testHelper = new TestHelper('Custom driver');

if (!class_exists(Phpfastcache\Drivers\Fakefiles\Item::class)
  || !class_exists(Phpfastcache\Drivers\Fakefiles\Driver::class)
  || !class_exists(Phpfastcache\Drivers\Fakefiles\Config::class)
) {
    $testHelper->assertFail('The php classes of driver "Fakefiles" do not exist');
    $testHelper->terminateTest();
} else {
    $testHelper->assertPass('The php classes of driver "Fakefiles" were found');
}

try {
    CacheManager::addCustomDriver('Fakefiles', \Phpfastcache\Drivers\Fakefiles\Driver::class);
    $testHelper->assertPass('No exception thrown while trying to add a custom driver');
} catch (\Throwable $e) {
    $testHelper->assertFail('An exception has been thrown while trying to add a custom driver');
}

try {
    CacheManager::addCustomDriver('Fakefiles', \Phpfastcache\Drivers\Fakefiles\Driver::class);
    $testHelper->assertFail('No exception thrown  while trying to re-add a the same custom driver');
} catch (\Throwable $e) {
    $testHelper->assertPass('An exception has been thrown while trying to re-add a the same custom driver');
}

try {
    CacheManager::addCustomDriver('', \Phpfastcache\Drivers\Fakefiles\Driver::class);
    $testHelper->assertFail('No exception thrown while trying to override an empty driver');
} catch (PhpfastcacheInvalidArgumentException $e) {
    $testHelper->assertPass('An exception has been thrown while trying to override an empty driver');
}

try {
    $cacheInstance = CacheManager::getInstance('Fakefiles', new Config(['customOption' => true]));
    $testHelper->assertPass('The custom driver is unavailable at the moment and no exception has been thrown.');
} catch (PhpfastcacheDriverCheckException $e) {
    $testHelper->assertPass('The custom driver is unavailable at the moment and the exception has been catch.');
}

CacheManager::removeCustomDriver('Fakefiles');

try {
    $cacheInstance = CacheManager::getInstance('Fakefiles');
    $testHelper->assertPass('The custom driver has been removed but is still active.');
} catch (PhpfastcacheDriverCheckException $e) {
    $testHelper->assertPass('The custom driver is unavailable at the moment and the exception has been catch.');
}

$testHelper->terminateTest();
