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

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mock/Autoload.php';
$testHelper = new TestHelper('Atomic Operations');
$pool = CacheManager::getInstance('Memstatic');
$testHelper->printInfoText('Testing APPEND/PREPEND methods...');

{
    $cacheItem = $pool->getItem($testHelper->getRandomKey());
    $cacheItem->set(['alpha', 'bravo']);
    $cacheItem->append('charlie');
    $pool->save($cacheItem);

    $target = ['alpha', 'bravo', 'charlie'];
    if(count(array_intersect($cacheItem->get(), $target)) === count($target)){
        $testHelper->assertPass('Atomic operation APPEND on ARRAY works as expected');
    } else {
        $testHelper->assertFail('Atomic operation APPEND on ARRAY did not worked as expected');
    }
}

// Reset pool
unset($target, $cacheItem);
$pool->clear();


{
    $cacheItem = $pool->getItem($testHelper->getRandomKey());
    $cacheItem->set('alpha_bravo');
    $cacheItem->append('_charlie');
    $pool->save($cacheItem);

    $target = 'alpha_bravo_charlie';
    if($cacheItem->get() === $target){
        $testHelper->assertPass('Atomic operation APPEND on STRING works as expected');
    } else {
        $testHelper->assertFail('Atomic operation APPEND on STRING did not worked as expected');
    }
}

// Reset pool
unset($target, $cacheItem);
$pool->clear();

{
    $cacheItem = $pool->getItem($testHelper->getRandomKey());
    $cacheItem->set(['bravo', 'charlie']);
    $cacheItem->prepend('alpha');
    $pool->save($cacheItem);

    $target = ['alpha', 'bravo', 'charlie'];
    if(count(array_intersect($cacheItem->get(), $target)) === count($target)){
        $testHelper->assertPass('Atomic operation PREPEND on ARRAY works as expected');
    } else {
        $testHelper->assertFail('Atomic operation PREPEND on ARRAY did not worked as expected');
    }
}

// Reset pool
unset($target, $cacheItem);
$pool->clear();

{
    $cacheItem = $pool->getItem($testHelper->getRandomKey());
    $cacheItem->set('bravo_charlie');
    $cacheItem->prepend('alpha_');
    $pool->save($cacheItem);

    $target = 'alpha_bravo_charlie';
    if($cacheItem->get() === $target){
        $testHelper->assertPass('Atomic operation PREPEND on STRING works as expected');
    } else {
        $testHelper->assertFail('Atomic operation PREPEND on STRING did not worked as expected');
    }
}

// Reset pool
unset($target, $cacheItem);
$pool->clear();

$testHelper->printInfoText('Testing INCREMENT/DECREMENT methods...');

{
    $cacheItem = $pool->getItem($testHelper->getRandomKey());
    $cacheItem->set(1330);
    $cacheItem->increment(3 + 4);
    $pool->save($cacheItem);

    if($cacheItem->get() === 1337){
        $testHelper->assertPass('Atomic operation INCREMENT on INT works as expected');
    } else {
        $testHelper->assertFail('Atomic operation INCREMENT on INT did not worked as expected');
    }
}

// Reset pool
unset($target, $cacheItem);
$pool->clear();

{
    $cacheItem = $pool->getItem($testHelper->getRandomKey());
    $cacheItem->set(1340);
    $cacheItem->decrement(4 - 1);
    $pool->save($cacheItem);

    if($cacheItem->get() === 1337){
        $testHelper->assertPass('Atomic operation DECREMENT on INT works as expected');
    } else {
        $testHelper->assertFail('Atomic operation DECREMENT on INT did not worked as expected');
    }
}

$testHelper->terminateTest();
