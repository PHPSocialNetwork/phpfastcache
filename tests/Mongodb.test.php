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
