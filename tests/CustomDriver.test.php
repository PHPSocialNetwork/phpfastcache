<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Fakefiles\Config;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Helper\CacheConditionalHelper as CacheConditional;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mock/autoload.php';
$testHelper = new TestHelper('Custom driver');

if (!class_exists(Phpfastcache\Drivers\Fakefiles\Item::class)
  || !class_exists(Phpfastcache\Drivers\Fakefiles\Driver::class)
  || !class_exists(Phpfastcache\Drivers\Fakefiles\Config::class)
) {
    $testHelper->printFailText('The php classes of driver "Fakefiles" does not exists');
    $testHelper->terminateTest();
} else {
    $testHelper->printPassText('The php classes of driver "Fakefiles" were found');
}

try {
    CacheManager::addCoreDriverOverride('Fakefiles', \Phpfastcache\Drivers\Fakefiles\Driver::class);
    $testHelper->printFailText('No exception thrown while trying to override an non-existing driver');
} catch (PhpfastcacheLogicException $e) {
    $testHelper->printPassText('An exception has been thrown while trying to override an non-existing driver');
}

try {
    CacheManager::addCoreDriverOverride('', \Phpfastcache\Drivers\Fakefiles\Driver::class);
    $testHelper->printFailText('No exception thrown while trying to override an empty driver');
} catch (PhpfastcacheInvalidArgumentException $e) {
    $testHelper->printPassText('An exception has been thrown while trying to override an empty driver');
}

CacheManager::addCoreDriverOverride('Files', \Phpfastcache\Drivers\Fakefiles\Driver::class);

try{
    $cacheInstance = CacheManager::getInstance('Files', new Config(['customOption' => true]));
    $testHelper->printPassText('The custom driver is unavailable at the moment and no exception has been thrown.');
}catch (PhpfastcacheDriverCheckException $e){
    $testHelper->printPassText('The custom driver is unavailable at the moment and the exception has been catch.');
}

CacheManager::removeCoreDriverOverride('Files');

try{
    $cacheInstance = CacheManager::getInstance('Files');
    $testHelper->printPassText('The custom driver has been removed but is still.');
}catch (PhpfastcacheDriverCheckException $e){
    $testHelper->printPassText('The custom driver is unavailable at the moment and the exception has been catch.');
}

$testHelper->terminateTest();