<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\ItemBatch;
use phpFastCache\EventManager;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../src/autoload.php';
$testHelper = new TestHelper('Cache Slams Protection');
$defaultDriver = (!empty($argv[ 1 ]) ? ucfirst($argv[ 1 ]) : 'Files');
$driverInstance = CacheManager::getInstance($defaultDriver, [
  'preventCacheSlams' => true,
  'cacheSlamsTimeout' => 20
]);

EventManager::getInstance()->onCacheGetItemInSlamBatch(function(ExtendedCacheItemPoolInterface $itemPool, ItemBatch $driverData, $cacheSlamsSpendSeconds) use ($testHelper){
    $testHelper->printText("Looping in batch for {$cacheSlamsSpendSeconds} second(s) with a batch from " . $driverData->getItemDate()->format(\DateTime::W3C));
});

$testHelper->runSubProcess('CacheSlamsProtection');
/**
 * Give some time to the
 * subprocess to start
 * just like a concurrent
 * php request
 */
usleep(mt_rand(250000, 800000));

$item = $driverInstance->getItem('TestCacheSlamsProtection');

/**
 * @see CacheSlamsProtection.subprocess.php:28
 */
if($item->isHit() && $item->get() === 1337){
    $testHelper->printPassText('The batch has expired and returned a non-empty item with expected value: ' . $item->get());
}else{
    $testHelper->printFailText('The batch has expired and returned an empty item with expected value: ' . print_r($item->get(), true));
}

/**
 * Cleanup the driver
 */
$driverInstance->deleteItem($item->getKey());

$testHelper->terminateTest();