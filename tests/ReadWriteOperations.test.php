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
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Drivers\Files\Config as FilesConfig;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Read/Write operations (I/O)');
CacheManager::setDefaultConfig(new ConfigurationOption(['path' => __DIR__ . '/../../cache']));

/**
 * @var ExtendedCacheItemInterface[] $items
 */
$items = [];
$instances = [];
$keys = [];

$dirs = [
    __DIR__ . '/../var/cache-cache/IO-',
    sys_get_temp_dir() . '/phpfastcache/IO-1',
    sys_get_temp_dir() . '/phpfastcache/IO-2',
];

foreach ($dirs as $dirIndex => $dir) {
    for ($i = 1; $i <= 20; ++$i) {
        $keys[$dirIndex][] = 'test' . $i;
    }

    for ($i = 1; $i <= 20; ++$i) {
        $cacheInstanceName = 'cacheInstance' . $i;

        $instances[$dirIndex][$cacheInstanceName] = CacheManager::getInstance('Files', new FilesConfig([
            'path' => $dir . str_pad($i, 3, '0', \STR_PAD_LEFT),
            'secureFileManipulation' => true,
            'securityKey' => '_cache',
        ]));

        foreach ($keys[$dirIndex] as $index => $key) {
            $items[$dirIndex][$index] = $instances[$dirIndex][$cacheInstanceName]->getItem($key);
            $items[$dirIndex][$index]->set("test-$dirIndex-$index")->expiresAfter(600);
            $instances[$dirIndex][$cacheInstanceName]->saveDeferred($items[$dirIndex][$index]);
        }
        $instances[$dirIndex][$cacheInstanceName]->commit();
        $instances[$dirIndex][$cacheInstanceName]->detachAllItems();
    }

    foreach ($instances[$dirIndex] as $cacheInstanceName => $instance) {
        foreach ($keys[$dirIndex] as $index => $key) {
            if ($instances[$dirIndex][$cacheInstanceName]->getItem($key)->get() === "test-$dirIndex-$index") {
                $testHelper->assertPass("Item #{$key} of instance #{$cacheInstanceName} of dir #{$dirIndex} has returned the expected value (" . gettype("test-$dirIndex-$index") . ":'" . "test-$dirIndex-$index" . "')");
            } else {
                $testHelper->assertFail("Item #{$key} of instance #{$cacheInstanceName} of dir #{$dirIndex} returned an unexpected value (" . gettype($instances[$dirIndex][$cacheInstanceName]->getItem($key)
                    ->get()) . ":'" . $instances[$dirIndex][$cacheInstanceName]->getItem($key)
                    ->get() . "') expected (" . gettype("test-$dirIndex-$index") . ":'" . "test-$dirIndex-$index" . "') \n");
            }
        }
        $instances[$dirIndex][$cacheInstanceName]->detachAllItems();
    }
}

foreach ($dirs as $dirIndex => $dir) {
    for ($i = 1; $i <= 20; ++$i) {
        $cacheInstanceName = 'cacheInstance' . $i;

        $testHelper->printDebugText(sprintf('Clearing cache instance %s#%s data', $dir, $cacheInstanceName));
        $instances[$dirIndex][$cacheInstanceName]->clear();
    }
}

$testHelper->terminateTest();
