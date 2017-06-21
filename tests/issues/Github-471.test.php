<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Drivers\Files\Driver as FilesDriver;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../src/autoload.php';
$testHelper = new TestHelper('Github issue #471 - Fallback must be a boolean');
CacheManager::setDefaultConfig(['path' => __DIR__ . '/../../cache']);

/**
 * Catch the E_USER_WARNING for the tests
 */
set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) use($testHelper) {
    if (0 === error_reporting()) {
        return false;
    }

    if($errno === E_USER_WARNING){
        define('E_USER_WARNING_RAISED', true);
    }else{
        $testHelper->printFailText('A unknown error (' . $errno . ') has been catched while the fallback driver got used.');
    }
});

$cacheInstance = CacheManager::getInstance('Cassandra', ['fallback' => 'Files']);

if(defined('E_USER_WARNING_RAISED')){
    $testHelper->printPassText('A E_USER_WARNING error has been catched while the fallback driver got used.');
}else{
    $testHelper->printFailText('No E_USER_WARNING were thrown while the fallback driver got used.');
}

if($cacheInstance instanceof FilesDriver){
    $key = 'test';
    $cacheItem = $cacheInstance->getItem($key);
    $cacheItem->set('value');
    $cacheInstance->save($cacheItem);
    $testHelper->printPassText('The variable $cacheInstance is an expected instance of FilesDriver');
}else if(is_object($cacheInstance)){
    $testHelper->printFailText(sprintf('The variable $cacheInstance is an expected instance of "%s"', get_class($cacheInstance)));
}else{
    $testHelper->printFailText(sprintf('The variable $cacheInstance is an expected variable type "%s"', gettype($cacheInstance)));
}

$testHelper->terminateTest();