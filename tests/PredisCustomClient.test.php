<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Tests\Helper\TestHelper;
use Phpfastcache\Drivers\Predis\Config as PredisConfig;
use Predis\Client as PredisClient;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Predis custom client');

try{
    if(!class_exists(PredisClient::class)){
      throw new PhpfastcacheDriverCheckException('Predis library is not installed');
    }

    $testHelper->mutePhpNotices();
    $predisClient = new PredisClient([
      'host' => '127.0.0.1',
      'port' =>  6379,
      'password' => null,
      'database' => 0,
    ]);
    $predisClient->connect();

    $cacheInstance = CacheManager::getInstance('Predis', (new PredisConfig())->setPredisClient($predisClient));
    $testHelper->runCRUDTests($cacheInstance);
}catch (\RedisException $e){
    $testHelper->assertFail('A Predis exception occurred: ' . $e->getMessage());
}

$testHelper->terminateTest();
