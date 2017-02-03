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
$testHelper = new TestHelper('Custom key hash function');

function myAwesomeHashFunction($string){
    return 'customHash.' . sha1($string);
}

$cacheInstance = CacheManager::getInstance('Files', ['defaultKeyHashFunction' => 'myAwesomeHashFunction']);

$item = $cacheInstance->getItem(str_shuffle(uniqid('pfc', true)));
$item->set(true)->expiresAfter(300);
$cacheInstance->save($item);

if($item->getEncodedKey() === 'customHash.' . sha1($item->getKey())){
    $testHelper->printPassText('The custom key hash function returned expected hash string: ' . $item->getEncodedKey());
}else{
    $testHelper->printFailText('The custom key hash function returned unexpected hash string: ' . $item->getEncodedKey());
}

$testHelper->terminateTest();