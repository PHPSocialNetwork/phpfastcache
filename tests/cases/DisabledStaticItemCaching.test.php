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
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Entities\ItemBatch;
use Phpfastcache\EventManager;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Testing disabling static item caching');
$defaultDriver = (!empty($argv[ 1 ]) ? ucfirst($argv[ 1 ]) : 'Files');
$driverInstance = CacheManager::getInstance($defaultDriver, new ConfigurationOption([
    'useStaticItemCaching' => false,
]));

if (!$testHelper->isHHVM()) {
    $testHelper->runSubProcess('DisabledStaticItemCaching');
    /**
     * Give some time to the
     * subprocess to start
     * just like a concurrent
     * php request
     */
    $item = $driverInstance->getItem('TestUseStaticItemCaching');
    $item->set('654321-fedcba');
    $driverInstance->save($item);
    $testHelper->runSubProcess('DisabledStaticItemCaching');
    usleep(random_int(250000, 800000));

    // We don't want to clear cache instance since we disabled the static item caching
    $item = $driverInstance->getItem('TestUseStaticItemCaching');

    /**
     * @see CacheSlamsProtection.subprocess.php:28
     */
    if ($item->isHit() && $item->get() === 'abcdef-123456') {
        $testHelper->assertPass('The static item caching being disabled, the cache item has been fetched straight from backend.' . $item->get());
    } else {
        $testHelper->assertFail('The static item caching may not have been disabled since the cache item value does not match the expected value.');
    }

    /**
     * Cleanup the driver
     */
    $driverInstance->deleteItem($item->getKey());
} else {
    $testHelper->assertSkip('Test ignored on HHVM builds due to sub-process issues with C.I.');
}

$testHelper->terminateTest();
