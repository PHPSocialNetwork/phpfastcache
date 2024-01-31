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
use Phpfastcache\Event\Events;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('EventManager (Unscoped)');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

$cacheInstance = CacheManager::getInstance($defaultDriver);
$eventInstance = $cacheInstance->getEventManager();
$testHelper->debugEvents($eventInstance);
$eventInstance->addListener(Events::CACHE_SAVE_ITEM, static function (\Phpfastcache\Event\Event\CacheItemPoolEventSaveItem $event) {
    if ($event->getCacheItem()->_getData() === 1000) {
        $event->getCacheItem()->increment(337);
    }
});

$eventInstance->addListener(Events::CACHE_ITEM_SET, static function (\Phpfastcache\Event\Event\CacheItemSetEvent $event) use ($testHelper) {
    try{
        $event->getEventReferenceParameter()->setParameterValue(1000);
        $testHelper->assertPass('The event reference parameter accepted a value type change');
    } catch(PhpfastcacheInvalidTypeException){
        $testHelper->assertFail('The event reference parameter denied a value type change');
    }
});

$cacheKey = 'testItem';
$cacheKey2 = 'testItem2';

$item = $cacheInstance->getItem($cacheKey);
$item->set(false)->expiresAfter(60);
$cacheInstance->save($item);

if ($cacheInstance->getItem($cacheKey)->get() === 1337) {
    $testHelper->assertPass('The dispatched event executed the custom callback to alter the item');
} else {
    $testHelper->assertFail("The dispatched event is not working properly, the expected value '1337', got '" . $cacheInstance->getItem($cacheKey)->get() . "'");
}
$cacheInstance->clear();
unset($item);
$eventInstance->unbindAllListeners();
$testHelper->debugEvents($eventInstance);

$eventInstance->addListener(Events::CACHE_SAVE_MULTIPLE_ITEMS, static function (\Phpfastcache\Event\Event\CacheSaveMultipleItemsItemPoolEvent $event) use ($testHelper) {
    $parameterValue = $event->getEventReferenceParameter()->getParameterValue();

    try{
        $event->getEventReferenceParameter()->setParameterValue(null);
        $testHelper->assertFail('The event reference parameter accepted a value type change');
    } catch(PhpfastcacheInvalidTypeException){
        $testHelper->assertPass('The event reference parameter denied a value type change');
        $event->getEventReferenceParameter()->setParameterValue([]);
    }

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
