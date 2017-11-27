<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\Helper\Psr16Adapter;
use phpFastCache\Helper\TestHelper;


chdir(__DIR__);
require_once __DIR__ . '/../../src/autoload.php';
$testHelper = new TestHelper('Github issue #545 - Psr16Adapter get item even if it is expired');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$Psr16Adapter = new Psr16Adapter($defaultDriver);
$ttl = 5;

$testHelper->printText('Preparing test item...');
$value = str_shuffle(uniqid('pfc', true));
$Psr16Adapter->set('test-key', $value, $ttl);
$testHelper->printText(sprintf('Sleeping for %d seconds...', $ttl + 1));

sleep($ttl + 1);

if(!$Psr16Adapter->has('test-key')){
    $testHelper->printPassText('1/2 [Testing has()] Psr16 adapter does not return an expired cache item anymore');
}else{
    $testHelper->printFailText('1/2 [Testing has()] Psr16 adapter returned an expired cache item');
}

if(!$Psr16Adapter->has('test-key')){
    $testHelper->printPassText('2/2 [Testing get()] Psr16 adapter does not return an expired cache item anymore');
}else{
    $testHelper->printFailText('2/2 [Testing get()] Psr16 adapter returned an expired cache item');
}

$testHelper->terminateTest();