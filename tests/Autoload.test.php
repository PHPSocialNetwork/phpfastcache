<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../lib/Phpfastcache/Autoload/Autoload.php';
$testHelper = new TestHelper('Autoload');

/**
 * Testing PhpFastCache autoload
 */
if (!class_exists('Phpfastcache\CacheManager')) {
    $testHelper->printFailText('Autoload failed to find the CacheManager');
}else{
    $testHelper->printPassText('Autoload successfully found the CacheManager');
}

/**
 * Testing Psr autoload
 */
if (!interface_exists('Psr\Cache\CacheItemInterface')) {
    $testHelper->printFailText('Autoload failed to find the Psr CacheItemInterface');
}else{
    $testHelper->printPassText('Autoload successfully found the Psr CacheItemInterface');
}

$testHelper->terminateTest();