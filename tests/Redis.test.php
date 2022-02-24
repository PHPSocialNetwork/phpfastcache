<?php

declare(strict_types=1);

/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Redis\Config as RedisConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Tests\Helper\TestHelper;
use Redis as RedisClient;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Redis bundled client');

try {
    if (!class_exists(RedisClient::class)) {
        throw new PhpfastcacheDriverCheckException('Unable to test Redis client because the extension seems to be missing');
    }
    $cacheInstance = CacheManager::getInstance('Redis', new RedisConfig());
    $testHelper->runCRUDTests($cacheInstance);
} catch (\RedisException $e) {
    $testHelper->assertFail('A Redis exception occurred: ' . $e->getMessage());
}

$testHelper->terminateTest();
