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
use Phpfastcache\Cluster\AggregatorInterface;
use Phpfastcache\Cluster\ClusterAggregator;
use Phpfastcache\Drivers\Files\Config as FilesConfig;
use Phpfastcache\Drivers\Sqlite\Config as SqliteConfig;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mock/Autoload.php';
$testHelper = new TestHelper('Master/Slave Replication Cluster');

CacheManager::clearInstances();

$clusterAggregator = new ClusterAggregator('test_20');
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Redis'));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Sqlite', new SqliteConfig(['securityKey' => 'unit_tests'])));
$clusterAggregator->aggregateDriver(CacheManager::getInstance('Files', new FilesConfig(['securityKey' => 'unit_tests'])));
$cluster = $clusterAggregator->getCluster(AggregatorInterface::STRATEGY_RANDOM_REPLICATION);
$cluster->clear();

$testHelper->runCRUDTests($cluster);

$testHelper->terminateTest();
