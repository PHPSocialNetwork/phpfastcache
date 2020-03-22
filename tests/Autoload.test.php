<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Psr\Cache\CacheItemInterface;

chdir(__DIR__);
require_once __DIR__ . '/../lib/Phpfastcache/Autoload/Autoload.php';
$exitCode = 0;

/**
 * Testing PhpFastCache autoload
 */
if (!class_exists(CacheManager::class)) {
    print '[FAIL] Autoload failed to find the CacheManager' . PHP_EOL;
    $exitCode = 255;
} else {
    print '[PASS] Autoload successfully found the CacheManager' . PHP_EOL;
}

/**
 * Testing Psr autoload
 */
if (!interface_exists(CacheItemInterface::class)) {
    print '[FAIL] Autoload failed to find the Psr CacheItemInterface' . PHP_EOL;
    $exitCode = 255;
} else {
    print '[PASS] Autoload successfully found the Psr CacheItemInterface' . PHP_EOL;
}

exit($exitCode);