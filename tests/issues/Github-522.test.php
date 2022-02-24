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
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #522 - Predis returns wrong type hints');
$testHelper->mutePhpNotices();
// Hide php Redis extension notice by using a little @
$cacheInstance = CacheManager::getInstance('Predis');
$stringObject = new stdClass();
$stringObject->test = '';

try {
    /*
     * Clear Predis cache
     */
    $cacheInstance->clear();
    for ($i = 0; $i < 1000; ++$i) {
        $stringObject->test .= md5(uniqid('pfc', true));
    }
    $stringObject->test = str_shuffle($stringObject->test);

    $item1 = $cacheInstance->getItem('item1');
    $item2 = $cacheInstance->getItem('item2');
    $item3 = $cacheInstance->getItem('item3');

    $item1->isHit() ?: $item1->set(clone $stringObject)->expiresAfter(20);
    $item2->isHit() ?: $item2->set(clone $stringObject)->expiresAfter(20);
    $item3->isHit() ?: $item3->set(clone $stringObject)->expiresAfter(20);

    $item1->isHit() ?: $cacheInstance->save($item1);
    $item2->isHit() ?: $cacheInstance->save($item2);
    $item3->isHit() ?: $cacheInstance->save($item3);

    $cacheInstance->deleteItem($item2->getKey());
    $cacheInstance->deleteItem($item3->getKey());
    $cacheInstance->detachAllItems();
    unset($item1, $item2, $item3);

    if ($cacheInstance->getItem('item1')->isHit() && $cacheInstance->getItem('item1')->get()->test === $stringObject->test) {
        $testHelper->assertPass('The cache item "item1" returned the expected value.');
    } else {
        $testHelper->assertFail('The cache item "item1" returned an expected value: ' . gettype($stringObject));
    }

    if (!$cacheInstance->getItem('item2')->isHit() && !$cacheInstance->getItem('item2')->isHit()) {
        $testHelper->assertPass('The cache items "item2, item3" are not stored in cache as expected.');
    } else {
        $testHelper->assertFail('The cache items "item2, item3" are unexpectedly stored in cache.');
    }

    $cacheInstance->clear();

    if (!$cacheInstance->getItem('item1')->isHit() && null === $cacheInstance->getItem('item1')->get()) {
        $testHelper->assertPass('After a cache clear the cache item "item1" is not stored in cache as expected.');
    } else {
        $testHelper->assertFail('After a cache clear the cache item "item1" is still unexpectedly stored in cache.');
    }
} catch (\Throwable $e) {
    $testHelper->assertFail('The test did not ended well, an error occurred: ' . $e->getMessage());
}

$testHelper->terminateTest();
