<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../src/autoload.php';
$testHelper = new TestHelper('Items tags features');
$defaultDriver = (!empty($argv[ 1 ]) ? ucfirst($argv[ 1 ]) : 'Files');
$driverInstance = CacheManager::getInstance($defaultDriver);

/**
 * Item tag test // Init tags/items
 */

$createItemsCallback = function() use ($driverInstance)
{
    $item1 = $driverInstance->getItem('tag-test1');
    $item2 = $driverInstance->getItem('tag-test2');
    $item3 = $driverInstance->getItem('tag-test3');

    $item1->set('item-test_1')
      ->expiresAfter(600)
      ->addTag('tag-test_1')
      ->addTag('tag-test_all')
      ->addTag('tag-test_all2')
      ->addTag('tag-test_all3');

    $item2->set('item-test_2')
      ->expiresAfter(600)
      ->addTag('tag-test_1')
      ->addTag('tag-test_2')
      ->addTag('tag-test_all')
      ->addTag('tag-test_all2')
      ->addTag('tag-test_all3');

    $item3->set('item-test_3')
      ->expiresAfter(600)
      ->addTag('tag-test_1')
      ->addTag('tag-test_2')
      ->addTag('tag-test_3')
      ->addTag('tag-test_all')
      ->addTag('tag-test_all2')
      ->addTag('tag-test_all3')
      ->addTag('tag-test_all4');

    $driverInstance->saveMultiple($item1, $item2, $item3);

    return [
      'item1' => $item1,
      'item2' => $item2,
      'item3' => $item3
    ];
};

/**
 * Item tag test // Step 1
 */
$testHelper->printNewLine()->printText('#1 Testing getter: getItemsByTag() // Expecting 3 results');
$createItemsCallback();

$tagsItems = $driverInstance->getItemsByTag('tag-test_all');
if(is_array($tagsItems))
{
    if(count($tagsItems) === 3)
    {
        foreach($tagsItems as $tagsItem)
        {
            if(!in_array($tagsItem->getKey(), ['tag-test1', 'tag-test2', 'tag-test3'])){
                $testHelper->printFailText('STEP#1 // Got unexpected tagged item key:' . $tagsItem->getKey());
                goto itemTagTest2;
            }
        }
        $testHelper->printPassText('STEP#1 // Successfully retrieved 3 tagged item keys');
    }
    else
    {
        $testHelper->printFailText('STEP#1 //Got wrong count of item:' . count($tagsItems));
        goto itemTagTest2;
    }
}
else
{
    $testHelper->printFailText('STEP#1 // Expected $tagsItems to be an array, got: ' . gettype($tagsItems));
    goto itemTagTest2;
}

/**
 * Item tag test // Step 2
 */
itemTagTest2:
$testHelper->printNewLine()->printText('#2 Testing getter: getItemsByTagsAll() // Expecting 3 results');
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();

$tagsItems = $driverInstance->getItemsByTagsAll(['tag-test_all', 'tag-test_all2', 'tag-test_all3']);

if(is_array($tagsItems))
{
    if(count($tagsItems) === 3)
    {
        foreach($tagsItems as $tagsItem)
        {
            if(!in_array($tagsItem->getKey(), ['tag-test1', 'tag-test2', 'tag-test3'])){
                $testHelper->printFailText('STEP#2 // Got unexpected tagged item key:' . $tagsItem->getKey());
                goto itemTagTest3;
            }
        }
        $testHelper->printPassText('STEP#2 // Successfully retrieved 3 tagged item key');
    }
    else
    {
        $testHelper->printFailText('STEP#2 // Got wrong count of item:' . count($tagsItems) );
        goto itemTagTest3;
    }
}
else
{
    $testHelper->printFailText('STEP#2 // Expected $tagsItems to be an array, got: ' . gettype($tagsItems));
    goto itemTagTest3;
}

/**
 * Item tag test // Step 3
 */
itemTagTest3:
$testHelper->printNewLine()->printText('#3 Testing getter: getItemsByTagsAll() // Expecting 1 result');
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();

$tagsItems = $driverInstance->getItemsByTagsAll(['tag-test_all', 'tag-test_all2', 'tag-test_all3', 'tag-test_all4']);

if(is_array($tagsItems))
{
    if(count($tagsItems) === 1)
    {
        if(isset($tagsItems['tag-test3']))
        {
            if($tagsItems['tag-test3']->getKey() !== 'tag-test3'){
                $testHelper->printFailText('STEP#3 // Got unexpected tagged item key:' . $tagsItems['tag-test3']->getKey());
                goto itemTagTest4;
            }
            $testHelper->printPassText('STEP#3 // Successfully retrieved 1 tagged item keys');
        }
        else
        {
            $testHelper->printFailText('STEP#3 // Got wrong array key, expected "tag-test3", got "' . key($tagsItems) . '"');
            goto itemTagTest4;
        }
    }
    else
    {
        $testHelper->printFailText('STEP#3 // Got wrong count of item:' . count($tagsItems));
        goto itemTagTest4;
    }
}
else
{
    $testHelper->printFailText('STEP#3 // Expected $tagsItems to be an array, got: ' . gettype($tagsItems));
    goto itemTagTest4;
}

/**
 * Item tag test // Step 4
 */
itemTagTest4:
$testHelper->printNewLine()->printText('#4 Testing deleter: deleteItemsByTag() // Expecting no item left');
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();
$driverInstance->deleteItemsByTag('tag-test_all');

if(count($driverInstance->getItemsByTag('tag-test_all')) > 0)
{
    $testHelper->printFailText('[FAIL] STEP#4 // Getter getItemsByTag() found item(s), possible memory leak');
}
else
{
    $testHelper->printPassText('STEP#4 // Getter getItemsByTag() found no item');
}

$i = 0;
while(++$i <= 3)
{
    if($driverInstance->getItem("tag-test{$i}")->isHit())
    {
        $testHelper->printFailText("STEP#4 // Item 'tag-test{$i}' should've been deleted and is still in cache storage");
    }
    else
    {
        $testHelper->printPassText("STEP#4 // Item 'tag-test{$i}' have been deleted and is no longer in cache storage");
    }
}

/**
 * Item tag test // Step 5
 */
itemTagTest5:
$testHelper->printNewLine()->printText('#5 Testing deleter: deleteItemsByTagsAll() // Expecting no item left');
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();

$driverInstance->deleteItemsByTagsAll(['tag-test_all', 'tag-test_all2', 'tag-test_all3']);

if(count($driverInstance->getItemsByTag('tag-test_all')) > 0)
{
    $testHelper->printFailText('STEP#5 // Getter getItemsByTag() found item(s), possible memory leak');
}
else
{
    $testHelper->printPassText('STEP#5 // Getter getItemsByTag() found no item');
}

$i = 0;
while(++$i <= 3)
{
    if($driverInstance->getItem("tag-test{$i}")->isHit())
    {
        $testHelper->printFailText("STEP#5 // Item 'tag-test{$i}' should've been deleted and is still in cache storage");
    }
    else
    {
        $testHelper->printPassText("STEP#5 // Item 'tag-test{$i}' have been deleted and is no longer in cache storage");
    }
}

/**
 * Item tag test // Step 6
 */
itemTagTest6:
$testHelper->printNewLine()->printText('#6 Testing deleter: deleteItemsByTagsAll() // Expecting a specific item to be deleted');
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();

/**
 *  Only item 'item-test_3' got all those tags
 */
$driverInstance->deleteItemsByTagsAll(['tag-test_all', 'tag-test_all2', 'tag-test_all3', 'tag-test_all4']);

if($driverInstance->getItem('item-test_3')->isHit())
{
    $testHelper->printFailText('STEP#6 // Getter getItem() found item \'item-test_3\', possible memory leak');
}
else
{
    $testHelper->printPassText('STEP#6 // Getter getItem() did not found item \'item-test_3\'');
}

/**
 * Item tag test // Step 7
 */
itemTagTest7:
$testHelper->printNewLine()->printText('#7 Testing appender: appendItemsByTagsAll() // Expecting items value to get an appended part of string');
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();
$appendStr = '$*#*$';
$driverInstance->appendItemsByTagsAll(['tag-test_all', 'tag-test_all2', 'tag-test_all3'], $appendStr);

foreach($driverInstance->getItems(['tag-test1', 'tag-test2', 'tag-test3']) as $item)
{
    if(strpos($item->get(), $appendStr) === false)
    {
        $testHelper->printFailText("STEP#7 // Item '{$item->getKey()}' does not have the string part '{$appendStr}' in it's value");
    }
    else
    {
        $testHelper->printPassText("STEP#7 // Item 'tag-test{$item->getKey()}' does have the string part '{$appendStr}' in it's value");
    }
}

/**
 * Item tag test // Step 7
 */
itemTagTest8:
$testHelper->printNewLine()->printText('#8 Testing prepender: prependItemsByTagsAll() // Expecting items value to get a prepended part of string');
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();
$prependStr = '&+_+&';
$driverInstance->prependItemsByTagsAll(['tag-test_all', 'tag-test_all2', 'tag-test_all3'], $prependStr);

foreach($driverInstance->getItems(['tag-test1', 'tag-test2', 'tag-test3']) as $item)
{
    if(strpos($item->get(), $prependStr) === false)
    {
        $testHelper->printFailText("STEP#8 // Item '{$item->getKey()}' does not have the string part '{$prependStr}' in it's value");
    }
    else
    {
        $testHelper->printPassText("STEP#8 // Item 'tag-test{$item->getKey()}' does have the string part '{$prependStr}' in it's value");
    }
}

$testHelper->terminateTest();