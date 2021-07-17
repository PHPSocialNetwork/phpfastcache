<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Memcached\Config as MemcachedConfig;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Memcached alternative configuration syntax');

$cacheInstanceDefSyntax = CacheManager::getInstance('Memcached');

$cacheInstanceOldSyntax = CacheManager::getInstance('Memcached', new MemcachedConfig([
    'servers' => [
        [
            'host' => '127.0.0.1',
            'port' => 11211,
            'saslUser' => null,
            'saslPassword' => null,
        ]
    ]
]));

$cacheInstanceNewSyntax = CacheManager::getInstance('Memcached', new MemcachedConfig([
    'host' => '127.0.0.1',
    'port' => 11211,
]));

$cacheKey = 'cacheKey';
$RandomCacheValue = str_shuffle(uniqid('pfc', true));

$cacheItem = $cacheInstanceDefSyntax->getItem($cacheKey);
$cacheItem->set($RandomCacheValue)->expiresAfter(600);
$cacheInstanceDefSyntax->save($cacheItem);
unset($cacheItem);
$cacheInstanceDefSyntax->detachAllItems();


$cacheItem = $cacheInstanceOldSyntax->getItem($cacheKey);
$cacheItem->set($RandomCacheValue)->expiresAfter(600);
$cacheInstanceOldSyntax->save($cacheItem);
unset($cacheItem);
$cacheInstanceOldSyntax->detachAllItems();

$cacheItem = $cacheInstanceNewSyntax->getItem($cacheKey);
$cacheItem->set($RandomCacheValue)->expiresAfter(600);
$cacheInstanceNewSyntax->save($cacheItem);
unset($cacheItem);
$cacheInstanceNewSyntax->detachAllItems();


if ($cacheInstanceDefSyntax->getItem($cacheKey)->isHit()) {
    $testHelper->assertPass('The default Memcached syntax is working well');
} else {
    $testHelper->assertFail('The default Memcached syntax is not working');
}

if ($cacheInstanceOldSyntax->getItem($cacheKey)->isHit()) {
    $testHelper->assertPass('The old Memcached syntax is working well');
} else {
    $testHelper->assertFail('The old Memcached syntax is not working');
}

if ($cacheInstanceNewSyntax->getItem($cacheKey)->isHit()) {
    $testHelper->assertPass('The new Memcached syntax is working well');
} else {
    $testHelper->assertFail('The new Memcached syntax is not working');
}

$cacheInstanceDefSyntax->clear();
$cacheInstanceOldSyntax->clear();
$cacheInstanceNewSyntax->clear();
$testHelper->terminateTest();
