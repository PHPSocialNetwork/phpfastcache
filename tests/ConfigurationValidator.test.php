<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Exceptions\phpFastCacheInvalidConfigurationException;
use Phpfastcache\Helper\TestHelper;
use Phpfastcache\Drivers\Files\Config as FilesConfig;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
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
  [
    'Files' =>[
      'unwantedOption' => new \StdClass,
    ],
  ],
];

foreach ($tests as $test) {
    try {
        CacheManager::getInstance(key($test), new FilesConfig(current($test)));
        $testHelper->printFailText('Configuration validator has failed to correctly validate a driver configuration option');
    } catch (phpFastCacheInvalidConfigurationException $e) {
        $testHelper->printPassText('Configuration validator has successfully validated a driver configuration option by throwing an Exception --- ' . $e->getMessage());
    }
}

$testHelper->terminateTest();