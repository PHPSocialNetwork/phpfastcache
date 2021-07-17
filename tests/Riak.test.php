<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Riak driver');

try{
    $cacheInstance = CacheManager::getInstance('Riak');
} catch (\Basho\Riak\Exception $e){
    /**
     * Riak is only available on Trusty dist.
     * @see https://docs.travis-ci.com/user/database-setup/#riak
     */
    if(stripos($e->getMessage(), 'Could not contact Riak Server') !== false){
        $testHelper->printSkipText('Riak server unavailable: ' . $e->getMessage());
        $testHelper->terminateTest();
    }
}


$cacheKey = str_shuffle(uniqid('pfc', true));
$cacheValue = str_shuffle(uniqid('pfc', true));

try{
    $item = $cacheInstance->getItem($cacheKey);
    $item->set($cacheValue)->expiresAfter(300);
    $cacheInstance->save($item);
    $testHelper->printPassText('Successfully saved a new cache item into Riak server');
}catch(PhpfastcacheDriverException $e){
    $testHelper->printFailText('Failed to save a new cache item into Riak server with exception: ' . $e->getMessage());
}


try{
    unset($item);
    $cacheInstance->detachAllItems();
    $item = $cacheInstance->getItem($cacheKey);

    if($item->get() === $cacheValue){
        $testHelper->printPassText('Getter returned expected value: ' . $cacheValue);
    }else{
        $testHelper->printFailText('Getter returned unexpected value, expecting "' . $cacheValue . '", got "' . $item->get() . '"');
    }
}catch(PhpfastcacheDriverException $e){
    $testHelper->printFailText('Failed to save a new cache item into Riak server with exception: ' . $e->getMessage());
}

try{
    unset($item);
    $cacheInstance->detachAllItems();
    $cacheInstance->clear();
    $item = $cacheInstance->getItem($cacheKey);

    if(!$item->isHit()){
        $testHelper->printPassText('Successfully cleared the Riak server, no cache item found');
    }else{
        $testHelper->printFailText('Failed to clear the Riak server, a cache item has been found');
    }
}catch(PhpfastcacheDriverException $e){
    $testHelper->printFailText('Failed to clear the Riak server with exception: ' . $e->getMessage());
}

$testHelper->terminateTest();
