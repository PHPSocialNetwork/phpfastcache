<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Memcached\Config as MemcachedConfig;
use Phpfastcache\Drivers\Memcache\Config as MemcacheConfig;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #675 - Configuration validation issue with Memcached socket (path)');

/**
 * MEMCACHED ASSERTIONS
 */
$testHelper->printInfoText('Testing MEMCACHED configuration validation errors...');
try {
    new MemcachedConfig([
        'servers' => [
            [
                'host' => '',
                'path' => '',
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHED] An empty host and path did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHED] An empty host and path thrown an exception: ' . $e->getMessage());
}

try {
    new MemcachedConfig([
        'servers' => [
            [
                'host' => '127.0.0.1',
                'path' => 'a/nice/path',
                'port' => 11211,
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHED] Both path and host configured did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHED] Both path and host configured thrown an exception: ' . $e->getMessage());
}

try {
    new MemcachedConfig([
        'servers' => [
            [
                'host' => '',
                'path' => 'a/nice/path',
                'port' => 11211,
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHED] Both path and port configured did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHED] Both path and port configured thrown an exception: ' . $e->getMessage());
}

try {
    new MemcachedConfig([
        'servers' => [
            [
                'host' => '',
                'path' => true,
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHED] Invalid non-string path value not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHED] Invalid non-string path value thrown an exception: ' . $e->getMessage());
}

try {
    new MemcachedConfig([
        'servers' => [
            [
                'host' => true,
                'path' => '',
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHED] Invalid non-string host value not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHED] Invalid non-string host value thrown an exception: ' . $e->getMessage());
}


try {
    new MemcachedConfig([
        'servers' => [
            [
                'host' => '127.0.0.1',
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHED] An host without port configured did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHED] An host without port configured thrown an exception: ' . $e->getMessage());
}

try {
    new MemcachedConfig([
        'servers' => [
            [
                'unknown_key_1' => '',
                'unknown_key_2' => true,
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHED] Unknowns keys configured did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHED] Unknowns keys configured thrown an exception: ' . $e->getMessage());
}

try {
    new MemcachedConfig([
        'servers' => [
            [
                'host' => '127.0.0.1',
                'path' => '',
                'port' => 11211,
                'saslUser' => true,
                'saslPassword' => [1337],
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHED] Both saslUser and saslPassword misconfigurations did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHED] Both saslUser and saslPassword misconfigurations thrown an exception: ' . $e->getMessage());
}


/**
 * MEMCACHE ASSERTIONS
 */
$testHelper->printNewLine();
$testHelper->printInfoText('Testing MEMCACHE configuration validation errors...');
try {
    new MemcacheConfig([
        'servers' => [
            [
                'host' => '',
                'path' => '',
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHE] An empty host and path did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHE] An empty host and path thrown an exception: ' . $e->getMessage());
}

try {
    new MemcacheConfig([
        'servers' => [
            [
                'host' => '127.0.0.1',
                'path' => 'a/nice/path',
                'port' => 11211,
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHE] Both path and host configured did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHE] Both path and host configured thrown an exception: ' . $e->getMessage());
}

try {
    new MemcacheConfig([
        'servers' => [
            [
                'host' => '',
                'path' => 'a/nice/path',
                'port' => 11211,
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHE] Both path and port configured did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHE] Both path and port configured thrown an exception: ' . $e->getMessage());
}

try {
    new MemcacheConfig([
        'servers' => [
            [
                'host' => '',
                'path' => true,
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHE] Invalid non-string path value not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHE] Invalid non-string path value thrown an exception: ' . $e->getMessage());
}

try {
    new MemcacheConfig([
        'servers' => [
            [
                'host' => true,
                'path' => '',
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHE] Invalid non-string host value not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHE] Invalid non-string host value thrown an exception: ' . $e->getMessage());
}


try {
    new MemcacheConfig([
        'servers' => [
            [
                'host' => '127.0.0.1',
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHE] An host without port configured did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHE] An host without port configured thrown an exception: ' . $e->getMessage());
}

try {
    new MemcacheConfig([
        'servers' => [
            [
                'unknown_key_1' => '',
                'unknown_key_2' => true,
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHE] Unknowns keys configured did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHE] Unknowns keys configured thrown an exception: ' . $e->getMessage());
}

try {
    new MemcacheConfig([
        'servers' => [
            [
                'host' => '',
                'path' => 'a/nice/path',
                'port' => 11211,
                'saslUser' => 'lorem',
                'saslPassword' => 'ipsum',
            ]
        ]
    ]);
    $testHelper->assertFail('[MEMCACHE] Unsupported SASL configuration did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('[MEMCACHE] Unsupported SASL configuration thrown an exception: ' . $e->getMessage());
}

/**
 * GOOD CONFIGURATIONS ASSERTIONS
 */
$testHelper->printNewLine();
$testHelper->printInfoText('Testing valid configurations...');
try {
    new MemcacheConfig([
        'servers' => [
            [
                'host' => '',
                'path' => 'a/nice/path',
                'port' => null,
            ]
        ]
    ]);
    $testHelper->assertPass('[MEMCACHE] Valid Memcache socket configuration did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertFail('[MEMCACHE] Valid Memcache socket configuration thrown an exception: ' . $e->getMessage());
}

try {
    new MemcacheConfig([
        'servers' => [
            [
                'host' => '127.0.0.1',
                'path' => '',
                'port' => 11211,
            ]
        ]
    ]);
    $testHelper->assertPass('[MEMCACHE] Valid Memcache host configuration did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertFail('[MEMCACHE] Valid Memcache host configuration thrown an exception: ' . $e->getMessage());
}

try {
    new MemcachedConfig([
        'servers' => [
            [
                'host' => '',
                'path' => 'a/nice/path',
                'port' => null,
            ]
        ]
    ]);
    $testHelper->assertPass('[MEMCACHED] Valid Memcached socket configuration did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertFail('[MEMCACHED] Valid Memcached socket configuration thrown an exception: ' . $e->getMessage());
}

try {
    new MemcachedConfig([
        'servers' => [
            [
                'host' => '127.0.0.1',
                'path' => '',
                'port' => 11211,
            ]
        ]
    ]);
    $testHelper->assertPass('[MEMCACHED] Valid Memcached host configuration did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertFail('[MEMCACHED] Valid Memcached host configuration thrown an exception: ' . $e->getMessage());
}

try {
    new MemcachedConfig([
        'servers' => [
            [
                'host' => '127.0.0.1',
                'path' => '',
                'port' => 11211,
                'saslUser' => 'lorem',
                'saslPassword' => 'ipsum',
            ]
        ]
    ]);
    $testHelper->assertPass('[MEMCACHED] Valid Memcached host + SASL authentication configuration did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertFail('[MEMCACHED] Valid Memcached host + SASL authentication configuration thrown an exception: ' . $e->getMessage());
}

/**
 * BASIC CONFIGURATIONS ASSERTIONS
 */
$testHelper->printNewLine();
$testHelper->printInfoText('Testing basic configurations...');

try {
    CacheManager::getInstance('Memcached', new MemcachedConfig());
    $testHelper->assertPass('[MEMCACHED] Default configuration did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertFail('[MEMCACHED] Default configuration thrown an exception: ' . $e->getMessage());
} catch (PhpfastcacheDriverCheckException $e){
    // If the driver fails to initialize it is not an issue, the validation process has at least succeeded
    $testHelper->assertPass('[MEMCACHED] Default configuration did not thrown an exception (with failed driver initialization)');
}

try {
    CacheManager::getInstance('Memcache', new MemcacheConfig());
    $testHelper->assertPass('[MEMCACHE] Default configuration did not thrown an exception');
} catch (PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertFail('[MEMCACHE] Default configuration thrown an exception: ' . $e->getMessage());
} catch (PhpfastcacheDriverCheckException $e){
    // If the driver fails to initialize it is not an issue, the validation process has at least succeeded
    $testHelper->assertPass('[MEMCACHE] Default configuration did not thrown an exception (with failed driver initialization)');
}

$testHelper->terminateTest();
