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
 * @method setTags($keyword, $value, $time , array("tag_a","b","c"));
 * @method set($keyword, $value, $time, array("tags" => array(1,2,3,4));
 * @method getTags(array("a","b","c"), $return_content = true | false);
 * @method getTags("a");
 * @method touchTags($tags = array(), $time);
 * @method increaseTags($tags = array(), $step );
 * @method decreaseTags($tags = array(), $step );
 * @method deleteTags($tags = array());
 */

use phpFastCache\CacheManager;
require_once('../src/autoload.php');

// Setup File Path on your config files
CacheManager::setup(array(
    "path" => "C:/tmp/", // or in windows "C:/tmp/"
));
CacheManager::CachingMethod("phpFastCache");

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

