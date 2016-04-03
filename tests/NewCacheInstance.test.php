<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
use phpFastCache\CacheManager;
use phpFastCache\Core\DriverInterface;

chdir(__DIR__);
require_once '../src/autoload.php';

$status = 0;
echo "Testing new cache instance\n";

/**
 * Testing memcached as it is declared in .travis.yml
 */
$driverInstance = CacheManager::getInstance();

if(!is_object($driverInstance) || !($driverInstance instanceof DriverInterface))
{
    echo '[FAIL] CacheManager::getInstance() returned wrong data:' . gettype($driverInstance) . "\n";
    $status = 1;
}
echo "[PASS] CacheManager::getInstance() returned expected object that implements DriverInterface\n";

exit($status);