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
require_once __DIR__ . '/mock/autoload.php';
$testHelper = new TestHelper('Custom namespaces');

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