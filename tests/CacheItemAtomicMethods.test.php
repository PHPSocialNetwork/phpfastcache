<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\CacheManager;
use Phpfastcache\Exceptions\PhpfastcacheInstanceNotFoundException;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Cache Item Atomic Methods');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

$driverInstance = CacheManager::getInstance($defaultDriver);
$testItem = $driverInstance->getItem('test-item');
$cacheTestData = 'I <3 PhpFastCache';

$testItem->set($cacheTestData)->expiresAfter(600);
$driverInstance->save($testItem);
unset($testItem);
$driverInstance->detachAllItems();

$testItem = $driverInstance->getItem('test-item');

if ($testItem->getLength() === \strlen($cacheTestData)) {
    $testHelper->printPassText('Atomic method getLength() returned the exact length');
}else{
    $testHelper->printPassText('Atomic method getLength() returned an unexpected length' . $testItem->getLength());
}

if (!$testItem->isNull()) {
    $testHelper->printPassText('Atomic method isNull() returned FALSE');
}else{
    $testHelper->printPassText('Atomic method isNull() returned TRUE');
}

if (!$testItem->isEmpty()) {
    $testHelper->printPassText('Atomic method isEmpty() returned FALSE');
}else{
    $testHelper->printPassText('Atomic method isEmpty() returned TRUE');
}



$testHelper->terminateTest();