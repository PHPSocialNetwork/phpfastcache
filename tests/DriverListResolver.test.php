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
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Driver list resolver');

$subClasses = [
    'Config',
    'Driver',
    'Item',
];

$driverList = CacheManager::getDriverList();

foreach ($driverList as $driver) {
    foreach ($subClasses as $subClass) {
        $className = "Phpfastcache\\Drivers\\{$driver}\\{$subClass}";
        if (class_exists($className)) {
            $testHelper->assertPass(sprintf('Found the %s %s class: "%s"', $driver, $subClass, $className));
        } else {
            $testHelper->assertFail(sprintf('Class "%s" not found', $className));
        }
    }
}

$testHelper->terminateTest();
