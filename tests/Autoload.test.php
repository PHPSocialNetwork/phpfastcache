<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
use phpFastCache\CacheManager;
chdir(__DIR__);
require_once '../src/autoload.php';

$status = 0;
echo "Testing autoload\n";

if(!class_exists('phpFastCache\CacheManager'))
{
    echo "[FAIL] Autoload failed to find the CacheManager\n";
    $status = 1;
}
echo "[PASS] Autoload successfully found the CacheManager\n";

exit($status);