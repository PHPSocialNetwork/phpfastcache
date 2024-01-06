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

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\CacheManager;
use Phpfastcache\Exceptions\PhpfastcacheInstanceNotFoundException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Clear single cache instance');
$defaultDriver = (!empty($argv[1]) ? \ucfirst($argv[1]) : 'Files');
$instanceId = str_shuffle(md5(\time() - \random_int(0, 86400)));
$instanceId2 = str_shuffle(md5(\time() - \random_int(0, 86400)));
$instanceId3 = str_shuffle(md5(\time() - \random_int(0, 86400)));
$instanceId4 = str_shuffle(md5(\time() - \random_int(0, 86400)));

$driverInstance = CacheManager::getInstance($defaultDriver, null, $instanceId);
$driverInstance2 = CacheManager::getInstance($defaultDriver, null, $instanceId2);
$driverInstance3 = CacheManager::getInstance($defaultDriver, null, $instanceId3);
$driverInstance4 = CacheManager::getInstance($defaultDriver, null, $instanceId4);

CacheManager::clearInstance($driverInstance2);

$cacheInstances = CacheManager::getInstances();
if (\count($cacheInstances) === 3) {
    $testHelper->assertPass('A single cache instance have been cleared');
} else {
    $testHelper->assertFail('A single cache instance have NOT been cleared');
}

$driverInstance2Hash = spl_object_hash($driverInstance2);
foreach ($cacheInstances as $cacheInstance) {
    $driverInstanceHash = spl_object_hash($cacheInstance);
    if ($driverInstanceHash !== $driverInstance2Hash) {
        $testHelper->assertPass("Compared cache instance #{$driverInstanceHash} does not match with previously cleared cache instance #" . $driverInstance2Hash);
    } else {
        $testHelper->assertFail("Compared cache instance  #{$driverInstanceHash} unfortunately match with previously cleared cache instance #" . $driverInstance2Hash);
    }
}
