<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../src/autoload.php';
$testHelper = new TestHelper('Read/Write operations (I/O)');
CacheManager::setDefaultConfig(array('path' => __DIR__ . '/../../cache'));

/**
 * @var $items ExtendedCacheItemInterface[]
 */
$items = [];
$instances = [];
$keys = [];

$dirs = [
  __DIR__ . '/../var/cache/IO-',
  sys_get_temp_dir() . '/phpfastcache/IO-1',
  sys_get_temp_dir() . '/phpfastcache/IO-2'
];


foreach ($dirs as $dirIndex => $dir) {
    for ($i = 1; $i <= 20; $i++)
    {
        $keys[$dirIndex][] = 'test' . $i;
    }

    for ($i = 1; $i <= 20; $i++)
    {
        $cacheInstanceName = 'cacheInstance' . $i;

        $instances[$dirIndex][$cacheInstanceName] = CacheManager::getInstance('Files',array('path' => $dir . str_pad($i, 3, '0', STR_PAD_LEFT)));

        foreach($keys[$dirIndex] as $index => $key)
        {
            $items[$dirIndex][$index] = $instances[$dirIndex][$cacheInstanceName]->getItem($key);
            $items[$dirIndex][$index]->set("test-$dirIndex-$index")->expiresAfter(600);
            $instances[$dirIndex][$cacheInstanceName]->saveDeferred($items[$dirIndex][$index]);
        }
        $instances[$dirIndex][$cacheInstanceName]->commit();
        $instances[$dirIndex][$cacheInstanceName]->detachAllItems();
    }

    foreach($instances[$dirIndex] as $cacheInstanceName => $instance)
    {
        foreach($keys[$dirIndex] as $index => $key)
        {
            if($instances[$dirIndex][$cacheInstanceName]->getItem($key)->get() === "test-$dirIndex-$index")
            {
                $testHelper->printPassText("Item #{$key} of instance #{$cacheInstanceName} of dir #{$dirIndex} has returned the expected value (" . gettype("test-$dirIndex-$index") . ":'" . "test-$dirIndex-$index" . "')");
            }
            else
            {
                $testHelper->printFailText("Item #{$key} of instance #{$cacheInstanceName} of dir #{$dirIndex} returned an unexpected value (" . gettype($instances[$dirIndex][$cacheInstanceName]->getItem($key)->get()) . ":'" . $instances[$dirIndex][$cacheInstanceName]->getItem($key)->get() . "') expected (" . gettype("test-$dirIndex-$index") . ":'" . "test-$dirIndex-$index" . "') \n");
            }
        }
        $instances[$dirIndex][$cacheInstanceName]->detachAllItems();
    }
}

foreach ($dirs as $dirIndex => $dir){
    for ($i = 1; $i <= 20; $i++)
    {
        $cacheInstanceName = 'cacheInstance' . $i;

        $testHelper->printText(sprintf('Clearing cache instance %s#%s data', $dir, $cacheInstanceName));
        $instances[$dirIndex][$cacheInstanceName]->clear();
    }
}

$testHelper->terminateTest();