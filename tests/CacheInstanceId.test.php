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
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('New cache instance');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$instanceId = str_shuffle(md5(time() - mt_rand(0, 86400)));

$driverInstance = CacheManager::getInstance($defaultDriver, null, $instanceId);

if ($driverInstance->getInstanceId() !== $instanceId) {
    $testHelper->assertFail('Unexpected instance ID: ' . $driverInstance->getInstanceId());
}else{
    $testHelper->assertPass('Got expected instance ID: ' . $instanceId);
}

try{
    CacheManager::getInstanceById(str_shuffle($instanceId));
    $testHelper->assertFail('Non-existing instance ID has thrown no exception');
}catch(PhpfastcacheInstanceNotFoundException $e){
    $testHelper->assertPass('Non-existing instance ID has thrown an exception');
}

$testHelper->terminateTest();
