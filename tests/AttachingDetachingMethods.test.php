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
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Tests\Helper\TestHelper;
use Psr\Cache\CacheItemPoolInterface;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('[A|De]ttaching methods');
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
    $key = 'test_attaching_detaching';

    $itemDetached = $driverInstance->getItem($key);
    $driverInstance->detachItem($itemDetached);
    $itemAttached = $driverInstance->getItem($key);

    if ($driverInstance->isAttached($itemDetached) !== true) {
        $testHelper->assertPass('ExtendedCacheItemPoolInterface::isAttached() identified $itemDetached as being detached.');
    } else {
        $testHelper->assertFail('ExtendedCacheItemPoolInterface::isAttached() failed to identify $itemDetached as to be detached.');
    }

    try {
        $driverInstance->attachItem($itemDetached);
        $testHelper->assertFail('ExtendedCacheItemPoolInterface::attachItem() attached $itemDetached without trowing an error.');
    } catch (PhpfastcacheLogicException $e) {
        $testHelper->assertPass('ExtendedCacheItemPoolInterface::attachItem() failed to attach $itemDetached by trowing a phpfastcacheLogicException exception.');
    }
}

$testHelper->terminateTest();
