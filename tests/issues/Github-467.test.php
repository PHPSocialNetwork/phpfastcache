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
use Phpfastcache\Drivers\Files\Config as FilesConfig;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #467 - Allow to specify the file extension in the File Driver');
CacheManager::setDefaultConfig(new FilesConfig(['path' => __DIR__ . '/../../cache']));

try{
    $cacheInstance = CacheManager::getInstance('Files', new FilesConfig(['cacheFileExtension' => 'php']));
    $testHelper->assertFail('No error thrown while trying to setup a dangerous file extension');
}catch(PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('An error has been thrown while trying to setup a dangerous file extension');
}

try{
    $cacheInstance = CacheManager::getInstance('Files', new FilesConfig(['cacheFileExtension' => '.cache']));
    $testHelper->assertFail('No error thrown while trying to setup a dotted file extension');
}catch(PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertPass('An error has been thrown while trying to setup a dotted file extension');
}

try{
    $cacheInstance = CacheManager::getInstance('Files', new FilesConfig(['cacheFileExtension' => 'cache']));
    $testHelper->assertPass('No error thrown while trying to setup a safe file extension');
}catch(PhpfastcacheInvalidConfigurationException $e){
    $testHelper->assertFail('An error has been thrown while trying to setup a safe file extension');
}

$testHelper->terminateTest();
