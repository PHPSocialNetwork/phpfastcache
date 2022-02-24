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
use Phpfastcache\Drivers\Predis\Config as PredisConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Tests\Helper\TestHelper;
use Predis\Client as PredisClient;
use Predis\Connection\ConnectionException as PredisConnectionException;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Predis custom client');

try {
    if (!class_exists(PredisClient::class)) {
        throw new PhpfastcacheDriverCheckException('Predis library is not installed');
    }

    $testHelper->mutePhpNotices();

    try {
        $predisClient = new PredisClient([
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'database' => 0,
        ]);
        $predisClient->connect();
    } catch (PredisConnectionException $e) {
        throw new PhpfastcacheDriverConnectException('Redis server unreachable.');
    }

    $cacheInstance = CacheManager::getInstance('Predis', (new PredisConfig())->setPredisClient($predisClient));
    $testHelper->runCRUDTests($cacheInstance);
} catch (\RedisException $e) {
    $testHelper->assertFail('A Predis exception occurred: ' . $e->getMessage());
}

$testHelper->terminateTest();
