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
use Phpfastcache\EventManager;
use Phpfastcache\Tests\Helper\TestHelper;
use Phpfastcache\Drivers\Solr\Config as SolrConfig;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Solr driver');

if(!class_exists(Psr\EventDispatcher\EventDispatcherInterface::class)) {
    $testHelper->assertSkip('PSR EventDispatcher is not installed !');
    $testHelper->terminateTest();
}

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

/**
 * Test of custom events
 */
$onSolrBuildEndpointCalled = false;
EventManager::getInstance()->onSolrBuildEndpoint(static function () use (&$onSolrBuildEndpointCalled, $testHelper){
    $testHelper->assertPass('Event "onSolrBuildEndpoint" has been called.');
    $onSolrBuildEndpointCalled = true;
});

$cacheInstance = CacheManager::getInstance('Solr', $solrConfig);

if(!$onSolrBuildEndpointCalled) {
    $testHelper->assertFail('Event "onSolrBuildEndpoint" has NOT been called.');
}

$testHelper->runCRUDTests($cacheInstance);

$testHelper->terminateTest();
