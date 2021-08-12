<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Couchdb\Config as CouchdbConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Couchdb driver');

$config = new CouchdbConfig();
$config->setDatabase('phpfastcache($test/-)+1337');
$config->setItemDetailedDate(true);
try{
    $cacheInstance = CacheManager::getInstance('Couchdb', $config);
} catch (PhpfastcacheDriverConnectException $e){
    try{
        $testHelper->printDebugText('Unable to connect to Couchdb as an anynymous, trying with default credential...');
        $config->setUsername('admin');
        $config->setPassword('travis');
        $cacheInstance = CacheManager::getInstance('Couchdb', $config);
    } catch(PhpfastcacheDriverConnectException $e){
        $testHelper->assertSkip('Couchdb server unavailable: ' . $e->getMessage());
        $testHelper->terminateTest();
    }
}

$testHelper->runCRUDTests($cacheInstance);
$testHelper->terminateTest();
