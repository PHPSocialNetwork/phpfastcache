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
use Phpfastcache\Drivers\Arangodb\Config as ArangodbConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Arangodb driver');

$config = new ArangodbConfig();

$config->setItemDetailedDate(true);
try {
    $config->setCollection('phpfastcache');
    $config->setAuthUser('phpfastcache');
    $config->setAuthPasswd('travis');
    $config->setDatabase('phpfastcache');
    $config->setConnectTimeout(5);
    $config->setAutoCreate(true);
/*    $config->setTraceFunction(\Closure::fromCallable(static function ($type, $data) use ($testHelper){
        $testHelper->printDebugText(sprintf('Trace for %s: %s', strtoupper($type), $data));
    }));*/
    $cacheInstance = CacheManager::getInstance('Arangodb', $config);
    $testHelper->runCRUDTests($cacheInstance);
} catch (PhpfastcacheDriverConnectException $e) {
    $testHelper->assertSkip('Arangodb server unavailable: ' . $e->getMessage());
    $testHelper->terminateTest();
}
$testHelper->terminateTest();
