<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
use phpFastCache\Api;
use phpFastCache\CacheManager;

chdir(__DIR__);
require_once __DIR__ . '/../src/autoload.php';
echo '[PhpFastCache API v' . Api::getVersion() . "]\n\n";

$defaultDriver = (!empty($argv[ 1 ]) ? ucfirst($argv[ 1 ]) : 'Files');
$status = 0;
echo "Testing items tags feature\n";

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

extract($createItemsCallback(), EXTR_OVERWRITE);

/**
 * Item tag test // Step 1
 */
echo "\n#1 Testing getter: getItemsByTag() // Expecting 3 results\n";

$tagsItems = $driverInstance->getItemsByTag('tag-test_all');
if(is_array($tagsItems))
{
    if(count($tagsItems) === 3)
    {
        foreach($tagsItems as $tagsItem)
        {
            if(!in_array($tagsItem->getKey(), ['tag-test1', 'tag-test2', 'tag-test3'])){
                echo '[FAIL] STEP#1 // Got unexpected tagged item key:' . $tagsItem->getKey()  .  "\n";
                $status = 1;
                goto itemTagTest2;
            }
        }
        echo "[PASS] STEP#1 // Successfully retrieved 3 tagged item keys\n";
    }
    else
    {
        echo '[FAIL] STEP#1 //Got wrong count of item:' . count($tagsItems) .  "\n";
        $status = 1;
        goto itemTagTest2;
    }
}
else
{
    echo '[FAIL] STEP#1 // Expected $tagsItems to be an array, got: ' . gettype($tagsItems) .  "\n";
    $status = 1;
    goto itemTagTest2;
}

/**
 * Item tag test // Step 2
 */
itemTagTest2:
echo "\n#2 Testing getter: getItemsByTagsAll() // Expecting 3 results\n";
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
                echo '[FAIL] STEP#2 // Got unexpected tagged item key:' . $tagsItem->getKey()  .  "\n";
                $status = 1;
                goto itemTagTest3;
            }
        }
        echo "[PASS] STEP#2 // Successfully retrieved 3 tagged item key\n";
    }
    else
    {
        echo '[FAIL] STEP#2 // Got wrong count of item:' . count($tagsItems) .  "\n";
        $status = 1;
        goto itemTagTest3;
    }
}
else
{
    echo '[FAIL] STEP#2 // Expected $tagsItems to be an array, got: ' . gettype($tagsItems) .  "\n";
    $status = 1;
    goto itemTagTest3;
}

/**
 * Item tag test // Step 3
 */
itemTagTest3:
echo "\n#3 Testing getter: getItemsByTagsAll() // Expecting 1 result\n";
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
                echo '[FAIL] STEP#3 // Got unexpected tagged item key:' . $tagsItems['tag-test3']->getKey()  .  "\n";
                $status = 1;
                goto itemTagTest4;
            }
            echo "[PASS] STEP#3 // Successfully retrieved 1 tagged item keys\n";
        }
        else
        {
            echo '[FAIL] STEP#3 // Got wrong array key, expected "tag-test3", got "' . key($tagsItems) .  "\"\n";
            $status = 1;
            goto itemTagTest4;
        }
    }
    else
    {
        echo '[FAIL] STEP#3 // Got wrong count of item:' . count($tagsItems) .  "\n";
        $status = 1;
        goto itemTagTest4;
    }
}
else
{
    echo '[FAIL] STEP#3 // Expected $tagsItems to be an array, got: ' . gettype($tagsItems) .  "\n";
    $status = 1;
    goto itemTagTest4;
}

/**
 * Item tag test // Step 4
 */
itemTagTest4:
echo "\n#4 Testing deleter: deleteItemsByTag() // Expecting no item left\n";
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();
$driverInstance->deleteItemsByTag('tag-test_all');

if(count($driverInstance->getItemsByTag('tag-test_all')) > 0)
{
    echo "[FAIL] STEP#4 // Getter getItemsByTag() found item(s), possible memory leak \n";
    $status = 1;
}
else
{
    echo "[PASS] STEP#4 // Getter getItemsByTag() found no item \n";
}

$i = 0;
while(++$i <= 3)
{
    if($driverInstance->getItem("tag-test{$i}")->isHit())
    {
        echo "[FAIL] STEP#4 // Item 'tag-test{$i}' should've been deleted and is still in cache storage \n";
        $status = 1;
    }
    else
    {
        echo "[PASS] STEP#4 // Item 'tag-test{$i}' have been deleted and is no longer in cache storage \n";
    }
}

/**
 * Item tag test // Step 5
 */
itemTagTest5:
echo "\n#5 Testing deleter: deleteItemsByTagsAll() // Expecting no item left\n";
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();

$driverInstance->deleteItemsByTagsAll(['tag-test_all', 'tag-test_all2', 'tag-test_all3']);

if(count($driverInstance->getItemsByTag('tag-test_all')) > 0)
{
    echo "[FAIL] STEP#5 // Getter getItemsByTag() found item(s), possible memory leak \n";
    $status = 1;
}
else
{
    echo "[PASS] STEP#5 // Getter getItemsByTag() found no item \n";
}

$i = 0;
while(++$i <= 3)
{
    if($driverInstance->getItem("tag-test{$i}")->isHit())
    {
        echo "[FAIL] STEP#5 // Item 'tag-test{$i}' should've been deleted and is still in cache storage \n";
        $status = 1;
    }
    else
    {
        echo "[PASS] STEP#5 // Item 'tag-test{$i}' have been deleted and is no longer in cache storage \n";
    }
}

/**
 * Item tag test // Step 6
 */
itemTagTest6:
echo "\n#6 Testing deleter: deleteItemsByTagsAll() // Expecting a specific item to be deleted\n";
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();

/**
 *  Only item 'item-test_3' got all those tags
 */
$driverInstance->deleteItemsByTagsAll(['tag-test_all', 'tag-test_all2', 'tag-test_all3', 'tag-test_all4']);

if($driverInstance->getItem('item-test_3')->isHit())
{
    echo "[FAIL] STEP#6 // Getter getItem() found item 'item-test_3', possible memory leak \n";
    $status = 1;
}
else
{
    echo "[PASS] STEP#6 // Getter getItem() did not found item 'item-test_3' \n";
}

/**
 * Item tag test // Step 7
 */
itemTagTest7:
echo "\n#7 Testing deleter: appendItemsByTagsAll() // Expecting items value to get an appended part of string\n";
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();
$appendStr = '$*#*$';
$driverInstance->appendItemsByTagsAll(['tag-test_all', 'tag-test_all2', 'tag-test_all3'], $appendStr);

foreach($driverInstance->getItems(['tag-test1', 'tag-test2', 'tag-test3']) as $item)
{
    if(strpos($item->get(), $appendStr) === false)
    {
        echo "[FAIL] STEP#7 // Item '{$item->getKey()}' does not have the string part '{$appendStr}' in it's value \n";
        $status = 1;
    }
    else
    {
        echo "[PASS] STEP#7 // Item 'tag-test{$item->getKey()}' does have the string part '{$appendStr}' in it's value \n";
    }
}

/**
 * Item tag test // Step 7
 */
itemTagTest8:
echo "\n#8 Testing deleter: prependItemsByTagsAll() // Expecting items value to get a prepended part of string\n";
$driverInstance->deleteItems(['item-test_1', 'item-test_2', 'item-test_3']);
$createItemsCallback();
$prependStr = '&+_+&';
$driverInstance->prependItemsByTagsAll(['tag-test_all', 'tag-test_all2', 'tag-test_all3'], $prependStr);

foreach($driverInstance->getItems(['tag-test1', 'tag-test2', 'tag-test3']) as $item)
{
    if(strpos($item->get(), $prependStr) === false)
    {
        echo "[FAIL] STEP#7 // Item '{$item->getKey()}' does not have the string part '{$prependStr}' in it's value \n";
        $status = 1;
    }
    else
    {
        echo "[PASS] STEP#7 // Item 'tag-test{$item->getKey()}' does have the string part '{$prependStr}' in it's value \n";
    }
}

exit($status);