<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Helper\TestHelper;


chdir(__DIR__);
require_once __DIR__ . '/../../src/autoload.php';
$testHelper = new TestHelper('Github issue #581 - Files driver "securityKey" option configuration not working as documented');
$cacheInstance = CacheManager::getInstance('Files');

/**
 * Force the HTTP_HOST in CLI to
 * simulate a Web request
 */
$_SERVER[ 'HTTP_HOST' ] = uniqid('test.', true) . '.pfc.net';

/**
 * Clear the cache to avoid
 * unexpected results
 */
$cacheInstance->clear();

$cacheKey = uniqid('ck', true);
$string = uniqid('pfc', true);
$testHelper->printText('Preparing test item...');

/**
 * Setup the cache item
 */
$cacheItem = $cacheInstance->getItem($cacheKey);
$cacheItem->set($string);
$cacheInstance->save($cacheItem);
unset($cacheItem);
$cacheInstance->detachAllItems();

if(strpos($cacheInstance->getPath(), 'phpfastcache' . DIRECTORY_SEPARATOR . $_SERVER[ 'HTTP_HOST' ]) !== false){
    $testHelper->printPassText('The "securityKey" option in automatic mode writes the HTTP_HOST directory as expected.');
}else{
    $testHelper->printFailText('The "securityKey" option in automatic mode leads to the following path: ' . $cacheInstance->getPath());
}

$testHelper->terminateTest();