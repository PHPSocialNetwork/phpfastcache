<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\Api;
use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('API class');

var_dump(Api::getPhpFastCacheVersion());

$testHelper->terminateTest();
