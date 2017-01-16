<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Helper\TestHelper;
use Psr\Cache\CacheItemPoolInterface;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Unsupported key characters');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$driverInstance = CacheManager::getInstance($defaultDriver);

try {
    $driverInstance->getItem('test{test');
    $testHelper->printFailText('1/4 An unsupported key character did not get caught by regular expression');
}catch(phpFastCacheInvalidArgumentException $e){
    $testHelper->printPassText('1/4 An unsupported key character has been caught by regular expression');
}

try {
    $driverInstance->getItem(':testtest');
    $testHelper->printFailText('2/4 An unsupported key character did not get caught by regular expression');
}catch(phpFastCacheInvalidArgumentException $e){
    $testHelper->printPassText('2/4 An unsupported key character has been caught by regular expression');
}

try {
    $driverInstance->getItem('testtest}');
    $testHelper->printFailText('3/4 An unsupported key character did not get caught by regular expression');
}catch(phpFastCacheInvalidArgumentException $e){
    $testHelper->printPassText('3/4 An unsupported key character has been caught by regular expression');
}

try {
    $driverInstance->getItem('testtest');
    $testHelper->printPassText('4/4 No exception caught while trying with a key without unsupported character');
}catch(phpFastCacheInvalidArgumentException $e){
    $testHelper->printFailText('4/4 An exception has been caught while trying with a key without unsupported character');
}

$testHelper->terminateTest();