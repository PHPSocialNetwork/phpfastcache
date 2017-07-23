<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheInvalidConfigurationException;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../src/autoload.php';
$testHelper = new TestHelper('Github issue #467 - Allow to specify the file extension in the File Driver');
CacheManager::setDefaultConfig(['path' => __DIR__ . '/../../cache']);

try{
    $cacheInstance = CacheManager::getInstance('Files', ['cacheFileExtension' => 'php']);
    $testHelper->printFailText('No error thrown while trying to setup a dangerous file extension');
}catch(phpFastCacheInvalidConfigurationException $e){
    $testHelper->printPassText('An error has been thrown while trying to setup a dangerous file extension');
}

try{
    $cacheInstance = CacheManager::getInstance('Files', ['cacheFileExtension' => '.cache']);
    $testHelper->printFailText('No error thrown while trying to setup a dotted file extension');
}catch(phpFastCacheInvalidConfigurationException $e){
    $testHelper->printPassText('An error has been thrown while trying to setup a dotted file extension');
}

try{
    $cacheInstance = CacheManager::getInstance('Files', ['cacheFileExtension' => 'cache']);
    $testHelper->printPassText('No error thrown while trying to setup a safe file extension');
}catch(phpFastCacheInvalidConfigurationException $e){
    $testHelper->printFailText('An error has been thrown while trying to setup a safe file extension');
}

$testHelper->terminateTest();