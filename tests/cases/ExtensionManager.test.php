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
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheExtensionNotInstalledException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../mock/Autoload.php';
$testHelper = new TestHelper('Apcu test (CRUD)');

try {
    $pool = CacheManager::getInstance('Arangodb');
    $testHelper->assertFail('CacheManager didnt thrown an Exception');
} catch (PhpfastcacheExtensionNotInstalledException) {
    $testHelper->assertPass('CacheManager thrown a PhpfastcacheExtensionNotInstalledException.');
} catch (\Throwable $e) {
    $testHelper->assertFail('CacheManager thrown a ' . $e::class);
}

try {
    $pool = CacheManager::getInstance(bin2hex(random_bytes(8)));
    $testHelper->assertFail('CacheManager didnt thrown an Exception');
} catch (PhpfastcacheDriverNotFoundException $e) {
    if ($e::class === PhpfastcacheDriverNotFoundException::class) {
        $testHelper->assertPass('CacheManager thrown a PhpfastcacheDriverNotFoundException.');
    } else {
        $testHelper->assertFail('CacheManager thrown a ' . $e::class);
    }
} catch (\Throwable $e) {
    $testHelper->assertFail('CacheManager thrown a ' . $e::class);
}

try {
    \Phpfastcache\ExtensionManager::registerExtension(
        'Extensiontest',
        \Phpfastcache\Extensions\Drivers\Extensiontest\Driver::class
    );
    $testHelper->assertPass('Registered a test extension.');
} catch (PhpfastcacheInvalidArgumentException) {
    $testHelper->assertFail('Failed to register a test extension.');
}

try {
    CacheManager::getInstance('Extensiontest');
    $testHelper->assertPass('Retrieved a test extension cache pool.');
} catch (PhpfastcacheDriverNotFoundException) {
    $testHelper->assertFail('Failed to retrieve a test extension cache pool.');
}


$testHelper->terminateTest();
