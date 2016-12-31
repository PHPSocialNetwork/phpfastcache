<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Helper\TestHelper;
use phpFastCache\Proxy\phpFastCacheAbstractProxy;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('phpFastCacheAbstractProxy class');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

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
    $testHelper->printFailText('$driverInstance->getItem() returned an invalid var type:' . gettype($driverInstance));
}else if(!($driverInstance->getItem('test') instanceof ExtendedCacheItemInterface)){
    $testHelper->printFailText('$driverInstance->getItem() returned an invalid class that does not implements ExtendedCacheItemInterface: ' . get_class($driverInstance));
}else{
    $testHelper->printPassText('$driverInstance->getItem() returned a valid class that implements ExtendedCacheItemInterface: ' . get_class($driverInstance));
}

$testHelper->terminateTest();