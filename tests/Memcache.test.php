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
