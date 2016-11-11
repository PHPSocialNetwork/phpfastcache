<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use Psr\Cache\CacheItemPoolInterface;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';

$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$status = 0;
echo "Testing [a|de]ttaching methods\n";

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
    $key = 'test_attaching_detaching';

    $itemDetached = $driverInstance->getItem($key);
    $driverInstance->detachItem($itemDetached);
    $itemAttached = $driverInstance->getItem($key);

    if($driverInstance->isAttached($itemDetached) !== true)
    {
        echo '[PASS] ExtendedCacheItemPoolInterface::isAttached() identified $itemDetached as being detached.' . "\n";
    }
    else
    {
        echo '[FAIL] ExtendedCacheItemPoolInterface::isAttached() failed to identify $itemDetached as to be detached.' . "\n";
        $status = 1;
    }

    try{
        $driverInstance->attachItem($itemDetached);
        echo '[FAIL] ExtendedCacheItemPoolInterface::attachItem() attached $itemDetached without trowing an error.' . "\n";
        $status = 1;
    }catch(\LogicException $e){
        echo '[PASS] ExtendedCacheItemPoolInterface::attachItem() failed to attach $itemDetached by trowing a LogicException exception.' . "\n";
    }

}

exit($status);