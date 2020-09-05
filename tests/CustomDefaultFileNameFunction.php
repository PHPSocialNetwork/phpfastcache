<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Drivers\Files\Driver;
use Phpfastcache\EventManager;
use Phpfastcache\Helper\TestHelper;

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
    $testHelper->printPassText('Custom filename function returned expected hash length');
}else{
    $testHelper->printFailText('Custom filename function did not returned expected hash length');
}

$testHelper->terminateTest();