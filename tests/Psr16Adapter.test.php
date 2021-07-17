<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\Helper\Psr16Adapter;
use Phpfastcache\Tests\Helper\TestHelper;


chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Psr16Adapter helper');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$Psr16Adapter = new Psr16Adapter($defaultDriver);

$value = str_shuffle(uniqid('pfc', true));

if(!$Psr16Adapter->has('test-key')){
    $testHelper->assertPass('1/9 Psr16 hasser returned expected boolean (false)');
}else{
    $testHelper->assertFail('1/9 Psr16 hasser returned unexpected boolean (true)');
}

$testHelper->printNewLine()->printText('Setting up value to "test-key"...')->printNewLine();
$Psr16Adapter->set('test-key', $value);

if($Psr16Adapter->get('test-key') === $value){
    $testHelper->assertPass('2/9 Psr16 getter returned expected value: ' . $value);
}else{
    $testHelper->assertFail('2/9 Psr16 getter returned unexpected value: ' . $value);
}

$testHelper->printNewLine()->printText('Deleting key "test-key"...')->printNewLine();
$Psr16Adapter->delete('test-key');

if(!$Psr16Adapter->has('test-key')){
    $testHelper->assertPass('3/9 Psr16 hasser returned expected boolean (false)');
}else{
    $testHelper->assertFail('3/9 Psr16 hasser returned unexpected boolean (true)');
}

$testHelper->printNewLine()->printText('Setting up value to "test-key, test-key2, test-key3"...')->printNewLine();
$Psr16Adapter->setMultiple([
  'test-key' => $value,
  'test-key2' => $value,
  'test-key3' => $value
]);


$values = $Psr16Adapter->getMultiple(['test-key', 'test-key2', 'test-key3']);
if(count(array_filter($values)) === 3){
    $testHelper->assertPass('4/9 Psr16 multiple getters returned expected values (3)');
}else{
    $testHelper->assertFail('4/9 Psr16 getters(3) returned unexpected values.');
}

$values = $Psr16Adapter->getMultiple(new ArrayObject(['test-key', 'test-key2', 'test-key3']));
if(count(array_filter($values)) === 3){
    $testHelper->assertPass('5/9 Psr16 multiple getters with Traversable returned expected values (3)');
}else{
    $testHelper->assertFail('5/9 Psr16 getters(3) with Traversable returned unexpected values.');
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
    $testHelper->assertPass('6/9 Psr16 hasser returned expected booleans (true)');
}else{
    $testHelper->assertFail('6/9 Psr16 hasser returned one or more unexpected boolean (false)');
}

$testHelper->printNewLine()->printText('Clearing whole cache ...')->printNewLine();
$Psr16Adapter->clear();

$testHelper->printText('Setting multiple values using a Traversable to "test-key, test-key2, test-key3"...')->printNewLine();
$Psr16Adapter->setMultiple(new ArrayObject([
  'test-key' => $value,
  'test-key2' => $value,
  'test-key3' => $value
]));

if($Psr16Adapter->has('test-key') && $Psr16Adapter->has('test-key2') && $Psr16Adapter->has('test-key3')){
    $testHelper->assertPass('7/9 Psr16 hasser returned expected booleans (true)');
}else{
    $testHelper->assertFail('7/9 Psr16 hasser returned one or more unexpected boolean (false)');
}

$testHelper->printNewLine()->printText('Deleting up keys "test-key, test-key2, test-key3"...')->printNewLine();
$Psr16Adapter->deleteMultiple(['test-key', 'test-key2', 'test-key3']);

if(!$Psr16Adapter->has('test-key') && !$Psr16Adapter->has('test-key2') && !$Psr16Adapter->has('test-key3')){
    $testHelper->assertPass('8/9 Psr16 hasser returned expected booleans (false)');
}else{
    $testHelper->assertFail('8/9 Psr16 hasser returned one or more unexpected boolean (true)');
}

$testHelper->printNewLine()->printText('Clearing whole cache ...')->printNewLine();
$Psr16Adapter->clear();
$testHelper->printText('Setting up value to "test-key, test-key2, test-key3"...')->printNewLine();
$Psr16Adapter->setMultiple([
  'test-key' => $value,
  'test-key2' => $value,
  'test-key3' => $value
]);

$testHelper->printText('Deleting up keys "test-key, test-key2, test-key3"... from a Traversable')->printNewLine();
$traversable = new ArrayObject(['test-key', 'test-key2', 'test-key3']);
$Psr16Adapter->deleteMultiple($traversable);

if(!$Psr16Adapter->has('test-key') && !$Psr16Adapter->has('test-key2') && !$Psr16Adapter->has('test-key3')){
    $testHelper->assertPass('9/9 Psr16 hasser returned expected booleans (false)');
}else{
    $testHelper->assertFail('9/9 Psr16 hasser returned one or more unexpected boolean (true)');
}

$testHelper->terminateTest();
