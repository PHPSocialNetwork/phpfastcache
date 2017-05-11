<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Helper\ActOnAll;
use phpFastCache\Helper\TestHelper;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('ActOnAll helper');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

/**
 * Testing memcached as it is declared in .travis.yml
 */
try{
    $filesInstance = CacheManager::getInstance('Files');
    $RedisInstance = CacheManager::getInstance('Redis');
    $MemcacheInstance = CacheManager::getInstance('Memcached');
}catch(phpFastCacheDriverCheckException $e){
    $testHelper->printSkipText($e->getMessage())->terminateTest();
}


$actOnAll = new ActOnAll();
$statsAry = $actOnAll->getStats();

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