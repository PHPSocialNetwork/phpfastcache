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
use Phpfastcache\Tests\Helper\TestHelper;
use Phpfastcache\Drivers\Solr\Config as SolrConfig;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Solr driver');

/** @var SolrConfig $solrConfig */
$solrConfig = $testHelper->preConfigure(new SolrConfig());
$solrConfig->setCoreName('phpfastcache'); // Optional: Default value
$solrConfig->setPort(8983); // Optional: Default value
$solrConfig->setHost('127.0.0.1'); // Optional: Default value
$solrConfig->setPath('/'); // Optional: Default value
$solrConfig->setScheme('http'); // Optional: Default value


/**
 * Optional:
 *
 * You can change the mapping schema used by Phpfastcache.
 * The keys are the Phpfastcache internal index. All required.
 * The values are the name of your Solr schema.
 */
$solrConfig->setMappingSchema($solrConfig::DEFAULT_MAPPING_SCHEMA);

/**
 * Optional:
 *
 * You can change the PSR-14 event dispatcher service used (and required) by solarium, by your own one.
 */
// $solrConfig->setEventDispatcher($yourEventDispatcher);

$cacheInstance = CacheManager::getInstance('Solr', $solrConfig);
$testHelper->runCRUDTests($cacheInstance, false);

$testHelper->terminateTest();
