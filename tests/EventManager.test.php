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
use Phpfastcache\EventManager;
use Phpfastcache\Tests\Helper\TestHelper;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('EventManager');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

$testHelper->debugEvents(EventManager::getInstance());
EventManager::getInstance()->onCacheSaveItem(static function(ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item){
    if($item->get() === 1000){
        $item->increment(337);
    }
});

$cacheInstance = CacheManager::getInstance($defaultDriver);
$cacheKey = 'testItem';

$item = $cacheInstance->getItem($cacheKey);
$item->set(1000)->expiresAfter(60);
$cacheInstance->save($item);


if($cacheInstance->getItem($cacheKey)->get() === 1337){
    $testHelper->assertPass('The dispatched event executed the custom callback to alter the item');
}else{
    $testHelper->assertFail("The dispatched event is not working properly, the expected value '1337', got '" . (int) $cacheInstance->getItem($cacheKey)->get() . "'");
}


$cacheInstance->clear();

$testHelper->terminateTest();
