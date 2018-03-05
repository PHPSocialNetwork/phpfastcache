<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Helper\CacheConditionalHelper as CacheConditional;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Custom namespaces');

$testDir = __DIR__ . '/../lib/Phpfastcache/CustomDriversPath/Files2/';

if (@!mkdir($testDir, 0777, true) && !is_dir($testDir))
{
    $testHelper->printFailText('Cannot create CustomDriversPath directory');
    $testHelper->terminateTest();
}

/**
 * The driver class string
 */
$driverClassString = <<<DRIVER_CLASS_STRING
<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace Phpfastcache\CustomDriversPath\Files2;
use Phpfastcache\Drivers\Files\Driver as FilesDriver;

/**
 * Class Driver
 * @package Phpfastcache\CustomDriversPath\Files2
 */
class Driver extends FilesDriver
{

}
DRIVER_CLASS_STRING;

/**
 * The item class string
 */
$itemClassString = <<<ITEM_CLASS_STRING
<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace Phpfastcache\CustomDriversPath\Files2;
use Phpfastcache\Drivers\Files\Item as FilesItem;

/**
 * Class Item
 * @package Phpfastcache\CustomDriversPath\Files2
 */
class Item extends FilesItem
{

}
ITEM_CLASS_STRING;

/**
 * The config class string
 */
$configClassString = <<<CONFIG_CLASS_STRING
<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace Phpfastcache\CustomDriversPath\Files2;
use Phpfastcache\Drivers\Files\Config as FilesConfig;

/**
 * Class Config
 * @package Phpfastcache\CustomDriversPath\Files2
 */
class Config extends FilesConfig
{

}
CONFIG_CLASS_STRING;


/**
 * Write the files
 */


if(!file_put_contents("{$testDir}Driver.php", $driverClassString)
  || !file_put_contents("{$testDir}Item.php", $itemClassString)
  || !file_put_contents("{$testDir}Config.php", $configClassString)
){
    $testHelper->printFailText('The php files of driver "Files2" were not written');
    $testHelper->terminateTest();
}else{
    $testHelper->printPassText('The php files of driver "Files2" were written');
}

/**
 * Then adjust the Chmod
 */
chmod("{$testDir}Driver.php", 0644);
chmod("{$testDir}Item.php", 0644);

if(!class_exists(Phpfastcache\CustomDriversPath\Files2\Item::class)
  || !class_exists(Phpfastcache\CustomDriversPath\Files2\Driver::class)
){
    $testHelper->printFailText('The php classes of driver "Files2" does not exists');
    $testHelper->terminateTest();
}else{
    $testHelper->printPassText('The php classes of driver "Files2" were found');
}

CacheManager::setNamespacePath(Phpfastcache\CustomDriversPath::class);
$cacheInstance = CacheManager::getInstance('Files2');
$cacheKey = 'cacheKey';
$RandomCacheValue = str_shuffle(uniqid('pfc', true));

/**
 * Existing cache item test
 */
$cacheItem = $cacheInstance->getItem($cacheKey);
$RandomCacheValue = str_shuffle(uniqid('pfc', true));
$cacheItem->set($RandomCacheValue);
$cacheInstance->save($cacheItem);

/**
 * Remove objects references
 */
$cacheInstance->detachAllItems();
unset($cacheItem);

$cacheValue = (new CacheConditional($cacheInstance))->get($cacheKey, function() use ($cacheKey, $testHelper, $RandomCacheValue){
    /**
     * No parameter are passed
     * to this closure
     */
    $testHelper->printFailText('Unexpected closure call.');
    return $RandomCacheValue . '-1337';
});

if($cacheValue === $RandomCacheValue){
    $testHelper->printPassText(sprintf('The cache promise successfully returned expected value "%s".', $cacheValue));
}else{
    $testHelper->printFailText(sprintf('The cache promise returned an unexpected value "%s".', $cacheValue));
}

$cacheInstance->clear();
$testHelper->terminateTest();