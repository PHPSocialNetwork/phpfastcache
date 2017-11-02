<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheLogicException;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../src/autoload.php';
$testHelper = new TestHelper('Cache option: itemDetailedDate');
$defaultDriver = (!empty($argv[ 1 ]) ? ucfirst($argv[ 1 ]) : 'Files');
$cacheInstance = CacheManager::getInstance($defaultDriver, ['itemDetailedDate' => true, 'path' => __DIR__ . '/../cache/']);
$cacheKey = 'cacheKey';
$RandomCacheValue = str_shuffle(uniqid('pfc', true));

$testHelper->printText('Preparing cache test item...');
$realCreationDate = new \DateTime();
$cacheItem = $cacheInstance->getItem($cacheKey);
$cacheItem->set($RandomCacheValue)->expiresAfter(60);
$cacheInstance->save($cacheItem);
$cacheInstance->detachAllItems();
$diffSeconds = 3;

unset($cacheItem);
for($i = 0; $i < $diffSeconds; $i++){
    $testHelper->printText(sprintf("Sleeping {$diffSeconds} seconds (%ds elapsed)", $i + 1));
    sleep(1);
}
$testHelper->printText('Triggering modification date...');

$cacheItem = $cacheInstance->getItem($cacheKey);
$cacheItem->set(str_shuffle($RandomCacheValue));
$realModificationDate = new \DateTime();
$cacheInstance->save($cacheItem);
$cacheInstance->detachAllItems();
unset($cacheItem);

for($i = 0; $i < $diffSeconds; $i++){
    $testHelper->printText(sprintf("Sleeping {$diffSeconds} additional seconds (%ds elapsed)", $i + 1));
    sleep(1);
}
$cacheItem = $cacheInstance->getItem($cacheKey);

try{
    $creationDate = $cacheItem->getCreationDate();
    if($creationDate instanceof \DateTimeInterface){
        $testHelper->printPassText('The method getCreationDate() returned a DateTimeInterface object');
        if($creationDate->format(DateTime::W3C) === $realCreationDate->format(DateTime::W3C)){
            $testHelper->printPassText('The item creation date effectively represents the real creation date (obviously).');
        }else{
            $testHelper->printFailText('The item creation date does not represents the real creation date.');
        }
    }else{
        $testHelper->printFailText('The method getCreationDate() does not returned a DateTimeInterface object, got: ' . var_export($creationDate, true));
    }
}catch(phpFastCacheLogicException $e){
    $testHelper->printFailText('The method getCreationDate() unexpectedly thrown a phpFastCacheLogicException');
}

try{
    $modificationDate = $cacheItem->getModificationDate();
    if($modificationDate instanceof \DateTimeInterface){
        $testHelper->printPassText('The method getModificationDate() returned a DateTimeInterface object');
        if($modificationDate->format(DateTime::W3C) === $realModificationDate->format(DateTime::W3C)){
            $testHelper->printPassText('The item modification date effectively represents the real modification date (obviously).');
        }else{
            $testHelper->printFailText('The item modification date does not represents the real modification date.');
        }
        /**
         * Using >= operator instead of === due to a possible micro time
         * offset that can often results to a value of 6 seconds (rounded)
         */
        if($modificationDate->getTimestamp() - $cacheItem->getCreationDate()->getTimestamp() >= $diffSeconds){
            $testHelper->printPassText("The item modification date is effectively {$diffSeconds} seconds greater than the creation date.");
        }else{
            $testHelper->printFailText('The item modification date effectively is not greater than the creation date.');
        }
    }else{
        $testHelper->printFailText('The method getModificationDate() does not returned a DateTimeInterface object, got: ' . var_export($modificationDate, true));
    }
}catch(phpFastCacheLogicException $e){
    $testHelper->printFailText('The method getModificationDate() unexpectedly thrown a phpFastCacheLogicException');
}

$cacheInstance->clear();
unset($cacheInstance);
CacheManager::clearInstances();
$cacheInstance = CacheManager::getInstance($defaultDriver, ['itemDetailedDate' => false]);

$testHelper->terminateTest();