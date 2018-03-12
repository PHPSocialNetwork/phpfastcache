<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Helper\CacheConditionalHelper as CacheConditional;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Custom namespaces');

$testDir = __DIR__ . '/../lib/Phpfastcache/Drivers/Fakefiles/';

if (!is_dir($testDir) && @!mkdir($testDir, 0777, true))
{
    $testHelper->printFailText('Cannot create Fakefiles directory');
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

namespace Phpfastcache\Drivers\Fakefiles;
use Phpfastcache\Drivers\Files\Driver as FilesDriver;

/**
 * Class Driver
 * @package Phpfastcache\Drivers\Files2
 */
class Driver extends FilesDriver
{
    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return false;
    }
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

namespace Phpfastcache\Drivers\Fakefiles;
use Phpfastcache\Drivers\Files\Item as FilesItem;

/**
 * Class Item
 * @package Phpfastcache\Drivers\Files2
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

namespace Phpfastcache\Drivers\Fakefiles;
use Phpfastcache\Drivers\Files\Config as FilesConfig;

/**
 * Class Config
 * @package Phpfastcache\Drivers\Files2
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
    $testHelper->printFailText('The php files of driver "Fakefiles" were not written');
    $testHelper->terminateTest();
}else{
    $testHelper->printPassText('The php files of driver "Fakefiles" were written');
}

/**
 * Then adjust the Chmod
 */
chmod("{$testDir}Driver.php", 0644);
chmod("{$testDir}Item.php", 0644);

if(!class_exists(Phpfastcache\Drivers\Fakefiles\Item::class)
  || !class_exists(Phpfastcache\Drivers\Fakefiles\Driver::class)
  || !class_exists(Phpfastcache\Drivers\Fakefiles\Config::class)
){
    $testHelper->printFailText('The php classes of driver "Fakefiles" does not exists');
    $testHelper->terminateTest();
}else{
    $testHelper->printPassText('The php classes of driver "Fakefiles" were found');
}

$cacheInstance = CacheManager::getInstance('Fakefiles', new ConfigurationOption([
  'fallback' => 'Files',
  'fallbackConfig' => new ConfigurationOption([
    'path' => sys_get_temp_dir()  . '/test'
  ])
]));

if($cacheInstance instanceof Phpfastcache\Drivers\Files\Driver){
    $testHelper->printPassText('The fallback "Files" has been used when the driver Fakefiles was unavailable');
}else{
    $testHelper->printPassText('The fallback "Files" has not been used when the driver Fakefiles was unavailable');
}

$testHelper->terminateTest();