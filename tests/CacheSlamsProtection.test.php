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
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Entities\ItemBatch;
use Phpfastcache\EventManager;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Cache Slams Protection');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$driverInstance = CacheManager::getInstance($defaultDriver, new ConfigurationOption([
    'preventCacheSlams' => true,
    'cacheSlamsTimeout' => 20,
]));

if (!$testHelper->isHHVM()) {
    EventManager::getInstance()->onCacheGetItemInSlamBatch(static function (ExtendedCacheItemPoolInterface $itemPool, ItemBatch $driverData, $cacheSlamsSpendSeconds) use ($testHelper): void {
        $testHelper->printText("Looping in batch for {$cacheSlamsSpendSeconds} second(s) with a batch from " . $driverData->getItemDate()->format(\DateTime::W3C));
    });

    $testHelper->runSubProcess('CacheSlamsProtection');
    /*
     * Give some time to the
     * subprocess to start
     * just like a concurrent
     * php request
     */
    usleep(random_int(250000, 800000));

    $item = $driverInstance->getItem('TestCacheSlamsProtection');

    /*
     * @see CacheSlamsProtection.subprocess.php:28
     */
    if ($item->isHit() && 1337 === $item->get()) {
        $testHelper->assertPass('The batch has expired and returned a non-empty item with expected value: ' . $item->get());
    } else {
        $testHelper->assertFail('The batch has expired and returned an empty item with expected value: ' . print_r($item->get(), true));
    }

    /*
     * Cleanup the driver
     */
    $driverInstance->deleteItem($item->getKey());
} else {
    $testHelper->assertSkip('Test ignored on HHVM builds due to sub-process issues with C.I.');
}

$testHelper->terminateTest();
