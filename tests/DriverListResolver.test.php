<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Helper\TestHelper;

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
        if(class_exists($className)){
            $testHelper->printPassText(sprintf('Found the %s %s class: "%s"', $driver, $subClass, $className));
        }else{
            $testHelper->printFailText(sprintf('Class "%s" not found', $className));
        }
    }
}

$testHelper->terminateTest();