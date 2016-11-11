<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use Psr\Cache\CacheItemPoolInterface;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';

$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$status = 0;
echo "Testing new cache instance\n";

/**
 * Testing memcached as it is declared in .travis.yml
 */
$driverInstance = CacheManager::getInstance($defaultDriver);

if (!is_object($driverInstance)) {
    echo '[FAIL] CacheManager::getInstance() returned an invalid variable type:' . gettype($driverInstance) . "\n";
    $status = 1;
}else if(!($driverInstance instanceof CacheItemPoolInterface)){
    echo '[FAIL] CacheManager::getInstance() returned an invalid class:' . get_class($driverInstance) . "\n";
    $status = 1;
}else{
    echo '[PASS] CacheManager::getInstance() returned a valid CacheItemPoolInterface object: ' . get_class($driverInstance) . "\n";
}

exit($status);