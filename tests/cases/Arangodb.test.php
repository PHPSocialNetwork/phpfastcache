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
use Phpfastcache\EventManager;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Tests\Helper\TestHelper;
use Phpfastcache\Tests\Config\ConfigFactory;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Arangodb driver');

try {
    EventManager::getInstance()->on(['ArangodbConnection', 'ArangodbCollectionParams'], static function() use ($testHelper){
        $args = func_get_args();
        $eventName = $args[array_key_last($args)];
        $testHelper->printDebugText(
            sprintf(
                'Arangodb db event "%s" has been triggered.',
                $eventName
            )
        );
    });
    $cacheInstance = CacheManager::getInstance('Arangodb', ConfigFactory::getDefaultConfig('Arangodb'));
    $testHelper->runCRUDTests($cacheInstance);
} catch (PhpfastcacheDriverConnectException $e) {
    $testHelper->assertSkip('Arangodb server unavailable: ' . $e->getMessage());
    $testHelper->terminateTest();
}
$testHelper->terminateTest();
