<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
use phpFastCache\CacheManager;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Helper\ActOnAll;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';

$status = 0;
echo "Testing ActOnAll helper\n";

/**
 * Testing memcached as it is declared in .travis.yml
 */
$RedisInstance = CacheManager::getInstance('Redis');
$filesInstance = CacheManager::getInstance('Files');
$MemcacheInstance = CacheManager::getInstance('Memcached');

$actOnAll = new ActOnAll();
$statsAry = $actOnAll->getStats();

if(is_array($statsAry)){
    if(count($statsAry) !== 3){
        $status = 1;
        echo '[FAIL] Wrong count of driverStatistics objects: Got ' . count($statsAry) . " element(s), expected 3\n";
        goto endOfTest;
    }

    foreach ($statsAry as $stat) {
        if(!is_object($stat) || !($stat instanceof driverStatistic)){
            echo "[FAIL] \$statsAry contains one element that is not an driverStatistic object\n";
            goto endOfTest;
        }
    }
    echo "[PASS] ActOnAll helper passed all tests\n";
}else{
    $status = 1;
    echo "[FAIL] \$statsAry is not an array\n";
}

endOfTest:
exit($status);