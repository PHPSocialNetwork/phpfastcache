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
use Phpfastcache\Event\Event;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheUnsupportedMethodException;
use Phpfastcache\Tests\Helper\TestHelper;
use Phpfastcache\Tests\Config\ConfigFactory;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\EventManager;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';

$testHelper = new TestHelper('Testing getAllItems() method');

/**
 * https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV5%CB%96%5D-Fetching-all-keys
 */
EventManager::getInstance()->on([Event::CACHE_GET_ALL_ITEMS], static function(ExtendedCacheItemPoolInterface $driver, EventReferenceParameter $referenceParameter) use ($testHelper, &$eventFlag){
    $callback = $referenceParameter->getParameterValue();
    $referenceParameter->setParameterValue(function(string $pattern) use ($callback, &$eventFlag, $testHelper) {
        $eventFlag = true;
        $testHelper->printInfoText('The custom event Event::CACHE_GET_ALL_ITEMS has been called.');
        return $callback($pattern);
    });
});
$drivers = ['Mongodb', 'Memstatic', 'Redis', 'RedisCluster', 'Solr', 'Firestore'];
$driversConfigs = ConfigFactory::getDefaultConfigs();
foreach ($drivers as $i => $driverName) {
    $testHelper->printNoteText(
        sprintf(
            "<blue>Testing</blue> <red>%s</red> <blue>against getAllItems() method</blue> (<yellow>%d</yellow>/<green>%d</green>)",
            strtoupper($driverName),
            $i + 1,
            count($drivers),
        )
    );
    try {
        $poolCache = CacheManager::getInstance($driverName, $driversConfigs[$driverName] ?? null);
    } catch (PhpfastcacheDriverConnectException|PhpfastcacheDriverCheckException $e){
        $testHelper->assertSkip(
            sprintf(
                "<blue>Skipping</blue> <red>%s</red> <blue>against getAllItems() method</blue> (Caught <red>%s</red>)",
                strtoupper($driverName),
                $e::class,
            )
        );
        $testHelper->printNewLine();
        continue;
    }

    $eventFlag = false;

    $poolCache->clear();
    $item1 = $poolCache->getItem('cache-test1');
    $item2 = $poolCache->getItem('cache-test2');
    $item3 = $poolCache->getItem('cache-test3');

    $item1->set('test1')->expiresAfter(3600);
    $item2->set('test2')->expiresAfter(3600);
    $item3->set('test3')->expiresAfter(3600);

    $poolCache->saveMultiple($item1, $item2, $item3);
    $poolCache->detachAllItems();
    unset($item1, $item2, $item3);


    $items = $poolCache->getAllItems();
    $itemCount = count($items);
    if ($itemCount === 3) {
        $testHelper->assertPass('getAllItems() returned 3 cache items as expected.');
    } else {
        $testHelper->assertFail(sprintf('getAllItems() unexpectedly returned %d cache items.', $itemCount));
    }

    foreach ($items as $key => $item) {
        if ($item->isHit()) {
            $testHelper->assertPass(sprintf('Item #%s is hit.', $item->getKey()));
        } else {
            $testHelper->assertFail(sprintf('Item #%s is not hit.', $item->getKey()));
        }

        if ($key === $item->getKey()) {
            $testHelper->assertPass(sprintf('Cache item #%s object is identified by its cache key.', $item->getKey()));
        } else {
            $testHelper->assertFail(sprintf('Cache item #%s object is identified by "%s".', $item->getKey(), $key));
        }
    }

    $testHelper->printNoteText("<blue>Testing getAllItems() method</blue> <yellow>(with pattern)</yellow>");

    try {
        $items = $poolCache->getAllItems('*test1*');
        if (count($items) === 1) {
            $testHelper->assertPass('Found 1 item using $pattern argument');
        } else {
            $testHelper->assertFail(sprintf('Found %d items using $pattern argument', count($items)));
        }

    } catch (PhpfastcacheInvalidArgumentException) {
        $testHelper->assertSkip("Pattern argument unsupported by $driverName driver");
    }

    $testHelper->printNewLine(1);
}

$filesCache = CacheManager::getInstance('Files');

try {
    $filesCache->getAllItems();
} catch (PhpfastcacheUnsupportedMethodException) {
    $testHelper->assertPass('getAllItems() is not supported by Files driver as expected and thrown a PhpfastcacheUnsupportedMethodException.');
} catch (\Throwable $e) {
    $testHelper->assertFail(sprintf('getAllItems() returned a exception "%s" instead of a PhpfastcacheUnsupportedMethodException.', $e::class));
}

if ($eventFlag) {
    $testHelper->assertPass('The Event::CACHE_GET_ALL_ITEMS has been triggered allowing the callback to be customized.');
}

$testHelper->terminateTest();
