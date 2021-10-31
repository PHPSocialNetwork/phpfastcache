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
