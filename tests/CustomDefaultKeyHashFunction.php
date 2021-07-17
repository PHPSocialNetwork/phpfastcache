<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Custom key hash function');
$driverInstance = CacheManager::getInstance(
    'sqlite',
    new ConfigurationOption(
        [
            'defaultKeyHashFunction' => static function (string $str) {
                return hash('sha256', $str);
            }
        ]
    )
);
$item = $driverInstance->getItem('TestCustomDefaultKeyHashFunction');
$item->set(bin2hex(random_bytes(random_int(10, 100))));
$driverInstance->save($item);

if (strlen($item->getEncodedKey()) === 64) {
    $testHelper->assertPass('Custom key hash function returned expected hash length');
} else {
    $testHelper->assertFail('Custom key hash function did not returned expected hash length');
}

$testHelper->terminateTest();
