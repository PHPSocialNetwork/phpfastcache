<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\Cache\ExtendedCacheItemInterface;
use phpFastCache\Proxy\phpFastCacheAbstractProxy;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';

$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$status = 0;
echo "Testing phpFastCacheAbstractProxy class\n";

/**
 * Dynamic driver-based example
 * Class myCustomCacheClass
 * @package MyCustom\Project
 */
class CustomMemcachedCacheClass extends phpFastCacheAbstractProxy
{
    public function __construct($driver = '', array $config = [])
    {
        global $defaultDriver;
        $driver = $defaultDriver;
        parent::__construct($driver, $config);
        /**
         * That's all !! Your cache class is ready to use
         */
    }
}


/**
 * Testing memcached as it is declared in .travis.yml
 */
$driverInstance = new CustomMemcachedCacheClass();

if (!is_object($driverInstance->getItem('test'))) {
    echo '[FAIL] $driverInstance->getItem() returned an invalid var type:' . gettype($driverInstance) . "\n";
    $status = 1;
}else if(!($driverInstance->getItem('test') instanceof ExtendedCacheItemInterface)){
    echo '[FAIL] $driverInstance->getItem() returned an invalid class that does not implements ExtendedCacheItemInterface: ' . get_class($driverInstance) . "\n";
    $status = 1;
}else{
    echo '[PASS] $driverInstance->getItem() returned a valid class that implements ExtendedCacheItemInterface: ' . get_class($driverInstance) . "\n";
}

exit($status);