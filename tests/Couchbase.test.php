<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Couchbase\Config as CouchbaseConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Couchbase driver');

$config = new CouchbaseConfig();
$config->setBucketName('phpfastcache');
$config->setItemDetailedDate(true);
try{
    $config->setUsername('test');
    $config->setPassword('phpfastcache');
    $cacheInstance = CacheManager::getInstance('Couchbase', $config);
} catch(PhpfastcacheDriverConnectException $e){
    $testHelper->assertSkip('Couchbase server unavailable: ' . $e->getMessage());
    $testHelper->terminateTest();
}
$testHelper->runCRUDTests($cacheInstance);
$testHelper->terminateTest();
