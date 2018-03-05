<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\CacheManager;
use Phpfastcache\Exceptions\phpFastCacheInstanceNotFoundException;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('New cache instance');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$instanceId = str_shuffle(md5(time() - mt_rand(0, 86400)));

$driverInstance = CacheManager::getInstance($defaultDriver, null, $instanceId);

if ($driverInstance->getInstanceId() !== $instanceId) {
    $testHelper->printFailText('Unexpected instance ID: ' . $driverInstance->getInstanceId());
}else{
    $testHelper->printPassText('Got expected instance ID: ' . $instanceId);
}

try{
    CacheManager::getInstanceById(str_shuffle($instanceId));
    $testHelper->printFailText('Non-existing instance ID has thrown no exception');
}catch(phpFastCacheInstanceNotFoundException $e){
    $testHelper->printPassText('Non-existing instance ID has thrown an exception');
}

$testHelper->terminateTest();