<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Tests\Helper\TestHelper;
use Phpfastcache\Drivers\Files\Config as FilesConfig;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
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
        $testHelper->assertFail('Configuration validator has failed to correctly validate a driver configuration option');
    } catch (PhpfastcacheInvalidTypeException|PhpfastcacheInvalidConfigurationException $e) {
        $testHelper->assertPass('Configuration validator has successfully validated a driver configuration option by throwing an Exception --- ' . $e->getMessage());
    }
}

$testHelper->terminateTest();
