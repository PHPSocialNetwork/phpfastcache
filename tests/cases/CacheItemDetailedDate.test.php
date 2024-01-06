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
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\CacheContract as CacheConditional;
use Phpfastcache\Tests\Helper\TestHelper;
use Psr\Cache\CacheItemPoolInterface;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Cache option: itemDetailedDate');
$defaultDriver = (!empty($argv[ 1 ]) ? ucfirst($argv[ 1 ]) : 'Files');
$cacheInstance = CacheManager::getInstance($defaultDriver, new ConfigurationOption([
  'itemDetailedDate' => true,
  'path' => __DIR__ . '/../cache/'
]));
$cacheKey = 'cacheKey';
$RandomCacheValue = str_shuffle(uniqid('pfc', true));

$testHelper->printText('Preparing cache test item...');
$realCreationDate = new \DateTime();
$cacheItem = $cacheInstance->getItem($cacheKey);
$cacheItem->set($RandomCacheValue)->expiresAfter(60);
$cacheInstance->save($cacheItem);
$cacheInstance->detachAllItems();
$diffSeconds = 3;

unset($cacheItem);
for ($i = 0; $i < $diffSeconds; $i++) {
    $testHelper->printText(sprintf("Sleeping {$diffSeconds} seconds (%ds elapsed)", $i + 1));
    sleep(1);
}
$testHelper->printText('Triggering modification date...');

$cacheItem = $cacheInstance->getItem($cacheKey);
$cacheItem->set(str_shuffle($RandomCacheValue));
$realModificationDate = new \DateTime();
$cacheInstance->save($cacheItem);
$cacheInstance->detachAllItems();
unset($cacheItem);

for ($i = 0; $i < $diffSeconds; $i++) {
    $testHelper->printText(sprintf("Sleeping {$diffSeconds} additional seconds (%ds elapsed)", $i + 1));
    sleep(1);
}
$cacheItem = $cacheInstance->getItem($cacheKey);

try {
    $creationDate = $cacheItem->getCreationDate();
    if ($creationDate instanceof \DateTimeInterface) {
        $testHelper->assertPass('The method getCreationDate() returned a DateTimeInterface object');
        if ($creationDate->format(DateTime::W3C) === $realCreationDate->format(DateTime::W3C)) {
            $testHelper->assertPass('The item creation date effectively represents the real creation date (obviously).');
        } else {
            $testHelper->assertFail('The item creation date does not represents the real creation date.');
        }
    } else {
        $testHelper->assertFail('The method getCreationDate() does not returned a DateTimeInterface object, got: ' . var_export($creationDate, true));
    }
} catch (PhpfastcacheLogicException $e) {
    $testHelper->assertFail('The method getCreationDate() unexpectedly thrown a phpfastcacheLogicException');
}

try {
    $modificationDate = $cacheItem->getModificationDate();
    if ($modificationDate instanceof \DateTimeInterface) {
        $testHelper->assertPass('The method getModificationDate() returned a DateTimeInterface object');
        if ($modificationDate->format(DateTime::W3C) === $realModificationDate->format(DateTime::W3C)) {
            $testHelper->assertPass('The item modification date effectively represents the real modification date (obviously).');
        } else {
            $testHelper->assertFail('The item modification date does not represents the real modification date.');
        }
        /**
         * Using >= operator instead of === due to a possible micro time
         * offset that can often results to a value of 6 seconds (rounded)
         */
        if ($modificationDate->getTimestamp() - $cacheItem->getCreationDate()->getTimestamp() >= $diffSeconds) {
            $testHelper->assertPass("The item modification date is effectively {$diffSeconds} seconds greater than the creation date.");
        } else {
            $testHelper->assertFail('The item modification date effectively is not greater than the creation date.');
        }
    } else {
        $testHelper->assertFail('The method getModificationDate() does not returned a DateTimeInterface object, got: ' . var_export($modificationDate, true));
    }
} catch (PhpfastcacheLogicException $e) {
    $testHelper->assertFail('The method getModificationDate() unexpectedly thrown a phpfastcacheLogicException');
}

$cacheInstance->clear();
unset($cacheInstance);
CacheManager::clearInstances();

$testHelper->terminateTest();
