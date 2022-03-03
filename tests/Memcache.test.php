<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Memcache\Config as MemcacheConfig;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Memcache driver');


$config = new MemcacheConfig([
    'servers' => [
        [
            'path' => '',
            'host' => '127.0.0.1',
            'port' => 11211,
        ]
    ]
]);
$config->setItemDetailedDate(true);
$cacheInstance = CacheManager::getInstance('Memcache', $config);
$testHelper->runCRUDTests($cacheInstance);
$testHelper->terminateTest();
