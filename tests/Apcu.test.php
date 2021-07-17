ClusterFullReplication.test.php<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Tests\Helper\TestHelper;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mock/Autoload.php';
$testHelper = new TestHelper('Apcu test (CRUD)');
$pool = CacheManager::getInstance('Apcu');
$pool->clear();
$testHelper->runCRUDTests($pool);
$testHelper->terminateTest();
