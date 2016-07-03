<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
use phpFastCache\CacheManager;

chdir(__DIR__);
require_once __DIR__ . '/../src/autoload.php';

$status = 0;
echo "Testing autoload\n";

/**
 * Testing PhpFastCache autoload
 */
if (!class_exists('phpFastCache\CacheManager')) {
    echo "[FAIL] Autoload failed to find the CacheManager\n";
    $status = 255;
}else{
    echo "[PASS] Autoload successfully found the CacheManager\n";
}

/**
 * Testing Psr autoload
 */
if (!interface_exists('Psr\Cache\CacheItemInterface')) {
    echo "[FAIL] Autoload failed to find the Psr CacheItemInterface\n";
    $status = 255;
}else{
    echo "[PASS] Autoload successfully found the Psr CacheItemInterface\n";
}

exit($status);