<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\driverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Helper\ActOnAll;
use Phpfastcache\Helper\TestHelper;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('ActOnAll helper');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

/**
 * Testing memcached as it is declared in .travis.yml
 */
try{
    CacheManager::getInstance('Files');
    CacheManager::getInstance('Redis');
    CacheManager::getInstance('Memcached');
}catch(PhpfastcacheDriverCheckException $e){
    try {
        CacheManager::clearInstances();
        CacheManager::getInstance('Files');
        CacheManager::getInstance('Sqlite');
        CacheManager::getInstance('Memstatic');
    }catch(PhpfastcacheDriverCheckException $e){
        $testHelper->printSkipText($e->getMessage())->terminateTest();
    }
}


$actOnAll = new ActOnAll();
$statsAry = $actOnAll->getStats();

$DriversItems = $actOnAll->getItems(['test-1', 'test-2']);


foreach ($DriversItems as $DriverName => $DriverItems) {
    foreach ($DriverItems as $driverItem) {
        if(!($driverItem instanceof ExtendedCacheItemInterface))
        {
            $testHelper->printFailText("The driver item from {$DriverName} does not implements ExtendedCacheItemInterface");
        }
        else
        {
            $testHelper->printPassText("The driver item '{$driverItem->getKey()}' from {$DriverName} does implements ExtendedCacheItemInterface");
        }
    }
}

/**
 * @todo MAKE SETTERS TESTS !!
 */

if(is_array($statsAry)){
    if(count($statsAry) !== 3){
        $testHelper->printFailText('Wrong count of driverStatistics objects: Got ' . count($statsAry) . " element(s), expected 3");
        goto endOfTest;
    }

    foreach ($statsAry as $stat) {
        if(!is_object($stat) || !($stat instanceof driverStatistic)){
            $testHelper->printFailText('$statsAry contains one element that is not a driverStatistic object');
            goto endOfTest;
        }
    }
    $testHelper->printPassText('ActOnAll helper passed all tests');
}else{
    $testHelper->printFailText('$statsAry is not an array');
}

endOfTest:
$testHelper->terminateTest();