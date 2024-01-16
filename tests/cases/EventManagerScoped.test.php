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
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\EventManager;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('EventManager (Scoped)');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

$filesCacheInstance = CacheManager::getInstance($defaultDriver);
$redisCacheInstance = CacheManager::getInstance('redis');

$globalEveryEventsEvents = [];
$filesEveryEventsEventEvents = [];
$redisEveryEventsEventEvents = [];

$globalGetItemEventManagerCount = 0;
$filesGetItemEventManagerCount = 0;
$redisGetItemEventManagerCount = 0;

EventManager::getInstance()->addGlobalListener(static function (string $eventName) use ($testHelper, &$globalEveryEventsEvents) {
    $testHelper->printInfoText(sprintf('<light_yellow>[Global]</light_yellow> <blue>Global "addGlobalListener" has been called for</blue> <magenta>"%s"</magenta>', $eventName));
    $globalEveryEventsEvents[$eventName] = ($globalEveryEventsEvents[$eventName] ?? 0) + 1;
}, 'GlobalEveryEvent');

EventManager::getInstance()->onCacheGetItem(static function (ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item, string $eventName) use ($testHelper, &$globalGetItemEventManagerCount) {
    $testHelper->assertPass('<light_yellow>[Global]</light_yellow> Global event manager received events from multiple pool instances');
    $globalGetItemEventManagerCount++;
});

$filesCacheInstance->getEventManager()->addGlobalListener(static function (string $eventName) use ($testHelper, &$filesEveryEventsEventEvents) {
    $testHelper->printInfoText(sprintf('<yellow>[Files]</yellow> <cyan>Scoped "addGlobalListener" has been called for</cyan> <magenta>"%s"</magenta>', $eventName));
    $filesEveryEventsEventEvents[$eventName] = ($filesEveryEventsEventEvents[$eventName] ?? 0) + 1;
}, 'GlobalEveryEvent');


$filesCacheInstance->getEventManager()->onCacheGetItem(static function (ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item, string $eventName) use ($testHelper, &$filesGetItemEventManagerCount)  {
    if($itemPool->getDriverName() === 'Files') {
        $testHelper->assertPass('<yellow>[Files]</yellow> Scoped event manager received only events of its own pool instance');
        $filesGetItemEventManagerCount++;
    }
});

$redisCacheInstance->getEventManager()->addGlobalListener(static function (string $eventName) use ($testHelper, &$redisEveryEventsEventEvents) {
    $testHelper->printInfoText(sprintf('<yellow>[Redis]</yellow> <cyan>Scoped "addGlobalListener" has been called for</cyan> <magenta>"%s"</magenta>', $eventName));
    $redisEveryEventsEventEvents[$eventName] = ($redisEveryEventsEventEvents[$eventName] ?? 0) + 1;
}, 'GlobalEveryEvent');

$redisCacheInstance->getEventManager()->onCacheGetItem(static function (ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item, string $eventName) use ($testHelper, &$redisGetItemEventManagerCount)  {
    if($itemPool->getDriverName() === 'Redis') {
        $testHelper->assertPass('<yellow>[Files]</yellow> Scoped event manager received only events of its own pool instance');
        $redisGetItemEventManagerCount++;
    }
});

// Trigger "CacheGetItem" event
$filesItem = $filesCacheInstance->getItem('FilesItem')->set('LoremIpsum');
$redisItem = $redisCacheInstance->getItem('RedisItem')->set('LoremIpsum');

$testHelper->printNewLine();
if($globalGetItemEventManagerCount === 2) {
    $testHelper->assertPass('<light_yellow>[Global]</light_yellow> Unscoped listener has been fired exactly 2 times');
} else {
    $testHelper->assertFail(sprintf('<light_yellow>[Global]</light_yellow> Unscoped listener has been fired exactly %s times instead of 2.', $globalGetItemEventManagerCount));
}

if($filesGetItemEventManagerCount === 1) {
    $testHelper->assertPass('<yellow>[Files]</yellow> Scoped listener has been fired exactly 1 time');
} else {
    $testHelper->assertFail(sprintf('<yellow>[Files]</yellow> Scoped listener has been fired exactly %s times instead of 1.', $filesGetItemEventManagerCount));
}

if($redisGetItemEventManagerCount === 1) {
    $testHelper->assertPass('<yellow>[Redis]</yellow> Scoped listener has been fired exactly 1 time');
} else {
    $testHelper->assertFail(sprintf('<yellow>[Redis]</yellow> Scoped listener has been fired exactly %s times instead of 1.', $redisGetItemEventManagerCount));
}

if($globalEveryEventsEvents['CacheGetItem'] === 2 && $globalEveryEventsEvents['CacheItemSet'] === 2) {
    $testHelper->assertPass('<light_yellow>[Global]</light_yellow> Unscoped listener has been fired exactly 2 times each CacheGetItem and CacheItemSet');
} else {
    $testHelper->assertFail(
        sprintf(
            '<light_yellow>[Global]</light_yellow> Unscoped listener has been fired exactly %s times CacheGetItem and %s times CacheItemSet.',
            $globalEveryEventsEvents['CacheGetItem'] ?? 0,
            $globalEveryEventsEvents['CacheItemSet'] ?? 0,
        )
    );
}

if($filesEveryEventsEventEvents['CacheGetItem'] === 1 && $filesEveryEventsEventEvents['CacheItemSet'] === 1) {
    $testHelper->assertPass('<light_yellow>[Files]</light_yellow> Scoped listener has been fired exactly 1 times each CacheGetItem and CacheItemSet');
} else {
    $testHelper->assertFail(
        sprintf(
            '<yellow>[Files]</yellow> Scoped listener has been fired exactly %s times CacheGetItem and %s times CacheItemSet.',
            $filesEveryEventsEventEvents['CacheGetItem'] ?? 0,
            $filesEveryEventsEventEvents['CacheItemSet'] ?? 0,
        )
    );
}


if($redisEveryEventsEventEvents['CacheGetItem'] === 1 && $redisEveryEventsEventEvents['CacheItemSet'] === 1) {
    $testHelper->assertPass('<light_yellow>[Redis]</light_yellow> Scoped listener has been fired exactly 1 times each CacheGetItem and CacheItemSet');
} else {
    $testHelper->assertFail(
        sprintf(
            '<yellow>[Redis]</yellow> Scoped listener has been fired exactly %s times CacheGetItem and %s times CacheItemSet.',
            $redisEveryEventsEventEvents['CacheGetItem'] ?? 0,
            $redisEveryEventsEventEvents['CacheItemSet'] ?? 0,
        )
    );
}



$testHelper->terminateTest();
