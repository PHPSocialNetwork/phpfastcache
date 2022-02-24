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
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Drivers\Dynamodb\Config as DynamodbConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Amazon Dynamodb driver');

$config = new DynamodbConfig();

try {
    $config->setItemDetailedDate(true);
    $config->setRegion('eu-west-3');
    $config->setEndpoint('dynamodb.eu-west-3.amazonaws.com');
    $config->setTable('phpfastcache');
    $cacheInstance = CacheManager::getInstance('Dynamodb', $config);

    $cacheInstance->getEventManager()->onDynamodbCreateTable(static function (ExtendedCacheItemPoolInterface $pool, array $params) use ($testHelper): void {
        $testHelper->printDebugText(
            sprintf(
                'Table created with the following parameters: %s',
                json_encode($params, \JSON_THROW_ON_ERROR)
            )
        );
    });

    $testHelper->runCRUDTests($cacheInstance, false);
} catch (PhpfastcacheDriverConnectException $e) {
    $testHelper->assertSkip('Dynamodb server unavailable: ' . $e->getMessage());
    $testHelper->terminateTest();
}
$testHelper->terminateTest();
