<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Tests\Helper\TestHelper;
use Phpfastcache\Drivers\RedisCluster\Config as RedisConfig;
use Redis as RedisClient;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Redis bundled client');

try {
    $config =  new RedisConfig();
    $config->setClusters( '127.0.0.1:7001', '127.0.0.1:7002', '127.0.0.1:7003', '127.0.0.1:7004', '127.0.0.1:7005', '127.0.0.1:7006');
    $config->setOptPrefix('pfc_');
    $config->setSlaveFailover(\RedisCluster::FAILOVER_ERROR);
    $cacheInstance = CacheManager::getInstance('RedisCluster', $config);
    $testHelper->runCRUDTests($cacheInstance);
} catch (\RedisException $e) {
    $testHelper->assertFail('A Redis exception occurred: ' . $e->getMessage());
}

$testHelper->terminateTest();
