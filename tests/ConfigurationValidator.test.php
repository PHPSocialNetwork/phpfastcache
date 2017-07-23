<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheInvalidConfigurationException;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../src/autoload.php';
$testHelper = new TestHelper('Configuration validator');


$tests = [
  [
    'Files' => [
      'path' => new \StdClass,
    ],
  ],
  [
    'Files' => [
      'htaccess' => new \StdClass,
    ],
  ],
  [
    'Files' => [
      'defaultTtl' => [],
    ],
  ],
];

foreach ($tests as $test) {
    try {
        CacheManager::getInstance(key($test), current($test));
        $testHelper->printFailText('Configuration validator has failed to correctly validate a driver configuration option');
    } catch (phpFastCacheInvalidConfigurationException $e) {
        $testHelper->printPassText('Configuration validator has successfully validated a driver configuration option by throwing an Exception');
    }
}

$testHelper->terminateTest();