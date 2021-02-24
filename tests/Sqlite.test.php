<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Sqlite');
CacheManager::setDefaultConfig(new ConfigurationOption(['path' => __DIR__ . '/../cache']));

$cacheInstance = CacheManager::getInstance('Sqlite');

$testHelper->runCRUDTests($cacheInstance);

$testHelper->terminateTest();
