<?php
/*
 * Tagging for Cache
 * Author: Khoaofgod@gmail.com
 */

use phpFastCache\CacheManager;
require_once("../src/phpFastCache/phpFastCache.php");

// Setup File Path on your config files
CacheManager::setup(array(
    "path" => "C:/tmp/", // or in windows "C:/tmp/"
));

// In your class, function, you can call the Cache
$cache = CacheManager::Files();

$keyword1   = "my_apple_keyword";
$value1     = "Apple is good";
$cache->set($keyword1,$value1, 300, array("tags"    =>  array("apple","laptop","computer")));
// OR: $cache->setTags($keyword1,$value1,300, array("computer","laptop"));


$keyword2   = "my_iphone_keyword";
$value2     = "Android is better than iphone for sure";
$cache->setTags($keyword2,$value2, 800, array("apple","cellphone"));


/// Time for the magic
$myAppleProducts = $cache->getTags(array("apple","laptop"));
echo "<pre>";
print_r($myAppleProducts);
echo "</pre>";
/*
 * Array
(
    [apple] => Array
        (
            [my_apple_keyword] => Apple is good
            [my_iphone_keyword] => Android is better than iphone for sure
        )

    [laptop] => Array
        (
            [my_apple_keyword] => Apple is good
        )

)
 */


/// Time for the magic
$cellphones = $cache->getTags(array("cellphone"));
echo "<pre>";
print_r($cellphones);
echo "</pre>";


/// Just need Keywords & Expired Time, No Caching Contents => put false at end
$myAppleProducts = $cache->getTags(array("apple","laptop"), false);
echo "<pre>";
print_r($myAppleProducts);
echo "</pre>";

/*
 * Array
(
    [apple] => Array
        (
            [my_apple_keyword] => 1455087283
            [my_iphone_keyword] => 1455087783
        )

    [laptop] => Array
        (
            [my_apple_keyword] => 1455087283
        )

)
 */

