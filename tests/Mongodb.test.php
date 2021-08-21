<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Mongodb\Config;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Mongodb driver');
$config = new Config();
$config->setItemDetailedDate(true)
    ->setDatabaseName('pfc_test')
    ->setCollectionName('pfc_' . str_pad('0', 3, random_int(1, 100)))
    ->setUsername('travis')
    ->setPassword('test');

$cacheInstance = CacheManager::getInstance('Mongodb', $config);
$testHelper->runCRUDTests($cacheInstance);
$testHelper->terminateTest();
