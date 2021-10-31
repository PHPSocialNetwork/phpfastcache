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
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Drivers\Files\Driver;
use Phpfastcache\EventManager;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Custom filename function');
$driverInstance = CacheManager::getInstance(
    'Files',
    new ConfigurationOption(
        [
            'defaultFileNameHashFunction' => static function (string $str) {
                return hash('sha256', $str);
            }
        ]
    )
);

$testPasses = false;
EventManager::getInstance()->onCacheWriteFileOnDisk(
    static function (ExtendedCacheItemPoolInterface $itemPool, $file) use ($driverInstance, &$testPasses) {
        /** @var $driverInstance Driver */
        $testPasses = strlen(basename($file, '.' . $driverInstance->getConfig()->getCacheFileExtension())) === 64;
    }
);

$item = $driverInstance->getItem('TestCustomDefaultFileNameFunction');
$item->set(bin2hex(random_bytes(random_int(10, 100))));
$driverInstance->save($item);

if ($testPasses) {
    $testHelper->assertPass('Custom filename function returned expected hash length');
} else {
    $testHelper->assertFail('Custom filename function did not returned expected hash length');
}

$testHelper->terminateTest();
