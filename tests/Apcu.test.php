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
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mock/Autoload.php';
$testHelper = new TestHelper('Apcu test (CRUD)');
$pool = CacheManager::getInstance('Apcu');
$pool->clear();
$testHelper->runCRUDTests($pool);
$testHelper->terminateTest();
