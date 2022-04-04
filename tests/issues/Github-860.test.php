<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Files\Config as FilesConfig;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #860 - Cache item throw an error on save with DateTimeImmutable date objects');

$config = new FilesConfig();
$testHelper->preConfigure($config);
$cacheInstance = CacheManager::getInstance('Files', $config);
$cacheInstance->clear();

try {
    $key = 'pfc_' . bin2hex(random_bytes(12));
    $item = $cacheInstance->getItem($key);
    $item->set(random_int(1000, 999999))
        ->setExpirationDate(new DateTimeImmutable('+1 month'))
        ->setCreationDate(new DateTimeImmutable())
        ->setModificationDate(new DateTimeImmutable('+1 week'));
    $cacheInstance->save($item);
    $cacheInstance->detachAllItems();
    $item = $cacheInstance->getItem($key);
    $testHelper->assertPass('Github issue #860 have not regressed.');
} catch (\TypeError $e) {
    $testHelper->assertFail('Github issue #860 have regressed, exception caught: ' . $e->getMessage());
}

