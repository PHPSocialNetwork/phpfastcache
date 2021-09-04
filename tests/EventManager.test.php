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
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('EventManager');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

$cacheInstance = CacheManager::getInstance($defaultDriver);
$eventInstance = $cacheInstance->getEventManager();
$testHelper->debugEvents($eventInstance);
$eventInstance->onCacheSaveItem(static function (ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item) {
    if ($item->get() === 1000) {
        $item->increment(337);
    }
});


$cacheKey = 'testItem';
$cacheKey2 = 'testItem2';

$item = $cacheInstance->getItem($cacheKey);
$item->set(1000)->expiresAfter(60);
$cacheInstance->save($item);

if ($cacheInstance->getItem($cacheKey)->get() === 1337) {
    $testHelper->assertPass('The dispatched event executed the custom callback to alter the item');
} else {
    $testHelper->assertFail("The dispatched event is not working properly, the expected value '1337', got '" . (int) $cacheInstance->getItem($cacheKey)->get() . "'");
}
$cacheInstance->clear();
unset($item);
$eventInstance->unbindAllEventCallbacks();
$testHelper->debugEvents($eventInstance);

$eventInstance->onCacheSaveMultipleItems(static function (ExtendedCacheItemPoolInterface $itemPool, EventReferenceParameter $eventReferenceParameter) use ($testHelper) {
    $parameterValue = $eventReferenceParameter->getParameterValue();
    $eventReferenceParameter->setParameterValue([]);

    if (is_array($parameterValue) && count($parameterValue) === 2) {
        $testHelper->assertPass('The event reference parameter returned an array of 2 cache items');
    } else {
        $testHelper->assertFail('The event reference parameter returned an unexpected value');
    }
});

$item = $cacheInstance->getItem($cacheKey);
$item2 = $cacheInstance->getItem($cacheKey2);
$item->set(1000)->expiresAfter(60);
$item2->set(2000)->expiresAfter(60);

$saveMultipleResult = $cacheInstance->saveMultiple($item, $item2);

if (!$saveMultipleResult) {
    $testHelper->assertPass('Method saveMultiple() returned false since it has nothing to save, as expected.');
} else {
    $testHelper->assertFail('Method saveMultiple() unexpectedly returned true.');
}

$testHelper->terminateTest();
