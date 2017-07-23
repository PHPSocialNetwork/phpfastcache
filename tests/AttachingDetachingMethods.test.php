<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheLogicException;
use phpFastCache\Helper\TestHelper;
use Psr\Cache\CacheItemPoolInterface;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('[A|De]ttaching methods');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

/**
 * Testing memcached as it is declared in .travis.yml
 */
$driverInstance = CacheManager::getInstance($defaultDriver);

if (!is_object($driverInstance)) {
    $testHelper->printFailText('CacheManager::getInstance() returned an invalid variable type:' . gettype($driverInstance));
}else if(!($driverInstance instanceof CacheItemPoolInterface)){
    $testHelper->printFailText('CacheManager::getInstance() returned an invalid class:' . get_class($driverInstance));
}else{
    $key = 'test_attaching_detaching';

    $itemDetached = $driverInstance->getItem($key);
    $driverInstance->detachItem($itemDetached);
    $itemAttached = $driverInstance->getItem($key);

    if($driverInstance->isAttached($itemDetached) !== true)
    {
        $testHelper->printPassText('ExtendedCacheItemPoolInterface::isAttached() identified $itemDetached as being detached.');
    }
    else
    {
        $testHelper->printFailText('ExtendedCacheItemPoolInterface::isAttached() failed to identify $itemDetached as to be detached.');
    }

    try{
        $driverInstance->attachItem($itemDetached);
        $testHelper->printFailText('ExtendedCacheItemPoolInterface::attachItem() attached $itemDetached without trowing an error.');
    }catch(phpFastCacheLogicException $e){
        $testHelper->printPassText('ExtendedCacheItemPoolInterface::attachItem() failed to attach $itemDetached by trowing a phpFastCacheLogicException exception.');
    }
}

$testHelper->terminateTest();