<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

/*
 * Tagging for Cache
 * Author: Khoaofgod@gmail.com
 *
 * @method search("keyword", $search_in_value = false | true);
 * @method search("/(apple)[\s]+/i", $search_in_value = false | true);
 */

use phpFastCache\CacheManager;
require_once("../src/autoload.php");

// Setup File Path on your config files
CacheManager::setup(array(
    "path" => "C:/tmp/", // or in Linux Path "/var/www/caching/"
    "allow_search"  =>  true    // <-- turn on Search Method
));

// In your class, function, you can call the Cache
$cache = CacheManager::Files();

echo "<b>Testing Search:</b>";

$keyword1   = "my_apple_keyword";
$value1     = "Apple is good";
$cache->set($keyword1,$value1);


$keyword2   = "my_iphone_keyword";
$value2     = "Android is better than iphone for sure";
$cache->set($keyword2,$value2);


/// Time for the magic
$myAppleProducts = $cache->search("apple");
echo "<pre>";
print_r($myAppleProducts);
echo "</pre>";
/*
 * Output: Array
(
    [my_apple_keyword] => 1612818738
)
 */

/// Time for the magic
$myAppleProducts = $cache->search("/my_[a-zA-Z]+_keyword/i", true);
echo "<pre>";
print_r($myAppleProducts);
echo "</pre>";
/*
 * Output:
 * Array
(
    [my_apple_keyword] => Apple is good
    [my_iphone_keyword] => Android is better than iphone for sure
)
 */

/// Time for the magic
$myAppleProducts = $cache->search("better", true);
echo "<pre>";
print_r($myAppleProducts);
echo "</pre>";

/*
 * Array
(
    [my_iphone_keyword] => Android is better than iphone for sure
)
 */

echo "<hr>";
echo "<b>Testing Tags:</b>";

include_once __DIR__."/Tags.YourCaching.php";