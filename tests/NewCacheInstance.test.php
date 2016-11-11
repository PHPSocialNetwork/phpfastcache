<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
use phpFastCache\Cache\ExtendedCacheItemPoolInterface;
use phpFastCache\CacheManager;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';

$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$status = 0;
echo "Testing new cache instance\n";

/**
 * Testing memcached as it is declared in .travis.yml
 */
$driverInstance = CacheManager::getInstance($defaultDriver);

if (!is_object($driverInstance) || !($driverInstance instanceof ExtendedCacheItemPoolInterface)) {
    echo '[FAIL] CacheManager::getInstance() returned wrong data:' . gettype($driverInstance) . "\n";
    $status = 1;
}
echo "[PASS] CacheManager::getInstance() returned expected object that implements ExtendedCacheItemPoolInterface\n";

exit($status);