<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Couchbasev3\Config as CouchbaseConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Couchbasev3 driver');

$config = new CouchbaseConfig();
$config->setBucketName('phpfastcache');
$config->setItemDetailedDate(true);
try{
    $config->setUsername('test');
    $config->setPassword('phpfastcache');
    $config->setBucketName('phpfastcache');
    $config->setScopeName('_default');
    $config->setCollectionName('_default');
    $cacheInstance = CacheManager::getInstance('Couchbasev3', $config);
} catch(PhpfastcacheDriverConnectException $e){
    $testHelper->assertSkip('Couchdb server unavailable: ' . $e->getMessage());
    $testHelper->terminateTest();
}
$testHelper->runCRUDTests($cacheInstance);
$testHelper->terminateTest();
