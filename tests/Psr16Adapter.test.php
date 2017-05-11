<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\Helper\Psr16Adapter;
use phpFastCache\Helper\TestHelper;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Psr16Adapter helper');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$Psr16Adapter = new Psr16Adapter($defaultDriver);

$value = str_shuffle(uniqid('pfc', true));

if(!$Psr16Adapter->has('test-key')){
    $testHelper->printPassText('1/6 Psr16 hasser returned expected boolean (false)');
}else{
    $testHelper->printFailText('1/6 Psr16 hasser returned unexpected boolean (true)');
}

$testHelper->printNewLine()->printText('Setting up value to "test-key"...')->printNewLine();
$Psr16Adapter->set('test-key', $value);

if($Psr16Adapter->get('test-key') === $value){
    $testHelper->printPassText('2/6 Psr16 getter returned expected value: ' . $value);
}else{
    $testHelper->printFailText('2/6 Psr16 getter returned unexpected value: ' . $value);
}

$testHelper->printNewLine()->printText('Deleting key "test-key"...')->printNewLine();
$Psr16Adapter->delete('test-key');

if(!$Psr16Adapter->has('test-key')){
    $testHelper->printPassText('3/6 Psr16 hasser returned expected boolean (false)');
}else{
    $testHelper->printFailText('3/6 Psr16 hasser returned unexpected boolean (true)');
}

$testHelper->printNewLine()->printText('Setting up value to "test-key, test-key2, test-key3"...')->printNewLine();
$Psr16Adapter->setMultiple([
  'test-key' => $value,
  'test-key2' => $value,
  'test-key3' => $value
]);


$values = $Psr16Adapter->getMultiple(['test-key', 'test-key2', 'test-key3']);
if(count(array_filter($values)) === 3){
    $testHelper->printPassText('4/6 Psr16 multiple getters returned expected values (3)');
}else{
    $testHelper->printFailText('4/6 Psr16 getters(3) returned unexpected values.');
}

$testHelper->printNewLine()->printText('Clearing whole cache ...')->printNewLine();
$Psr16Adapter->clear();

$testHelper->printText('Setting up value to "test-key, test-key2, test-key3"...')->printNewLine();
$Psr16Adapter->setMultiple([
  'test-key' => $value,
  'test-key2' => $value,
  'test-key3' => $value
]);

if($Psr16Adapter->has('test-key') && $Psr16Adapter->has('test-key2') && $Psr16Adapter->has('test-key3')){
    $testHelper->printPassText('5/6 Psr16 hasser returned expected booleans (true)');
}else{
    $testHelper->printFailText('5/6 Psr16 hasser returned one or more unexpected boolean (false)');
}

$testHelper->printNewLine()->printText('Deleting up keys "test-key, test-key2, test-key3"...')->printNewLine();
$Psr16Adapter->deleteMultiple(['test-key', 'test-key2', 'test-key3']);

if(!$Psr16Adapter->has('test-key') && !$Psr16Adapter->has('test-key2') && !$Psr16Adapter->has('test-key3')){
    $testHelper->printPassText('6/6 Psr16 hasser returned expected booleans (false)');
}else{
    $testHelper->printFailText('6/6 Psr16 hasser returned one or more unexpected boolean (true)');
}

$testHelper->terminateTest();