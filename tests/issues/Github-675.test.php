<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Memcached\Config;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #675 - Memcached ignores custom host/port configurations');

try {
    $config = new Config();
    $config->setServers([
        [
            'invalidHostKey' => '120.0.13.37',
            'invalidPortKey' => 8080,
            'saslUser' => false,
            'saslPassword' => false,
        ]
    ]);
    $testHelper->assertFail('1/4 Memcached config accepted unknown key(s) from $servers array.');
} catch (PhpfastcacheInvalidConfigurationException $e) {
    $testHelper->assertPass('1/4 Memcached config detected unknown key(s) from $servers array: ' . $e->getMessage());
}

try {
    $config = new Config();
    $config->setServers([
        [
            'host' => '120.0.13.37',
            'unwantedKey' => '120.0.13.37',
            'unwantedKey2' => '120.0.13.37',
            'port' => 8080,
            'saslUser' => false,
            'saslPassword' => false,
        ]
    ]);
    $testHelper->assertFail('2/4 Memcached config accepted unwanted key(s) from $servers array.');
} catch (PhpfastcacheInvalidConfigurationException $e) {
    $testHelper->assertPass('2/4 Memcached config detected unwanted key(s) from $servers array: ' . $e->getMessage());
}

try {
    $config = new Config();
    $config->setServers([
        [
            'host' => '120.0.13.37',
            'port' => '8080',
            'saslUser' => false,
            'saslPassword' => false,
        ]
    ]);
    $testHelper->assertFail('3/4 Memcached config does not detected invalid types fort host and port');
} catch (PhpfastcacheInvalidConfigurationException $e) {
    $testHelper->assertPass('3/4 Memcached config detected invalid types fort host and port: ' . $e->getMessage());
}

try {
    $config = new Config();
    $config->setHost('255.255.255.255');
    $config->setPort(1337);
    $cacheInstance = CacheManager::getInstance('Memcached', $config);
    $testHelper->assertFail('4/4 Memcached succeeded to connect with invalid host/port specified (used default combination of "$config->servers")');
} catch (PhpfastcacheDriverException $e) {
    $testHelper->assertPass('4/4 Memcached failed to connect with invalid host/port specified');
}

$testHelper->terminateTest();
