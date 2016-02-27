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
require_once("../src/autoload.php");

// Setup File Path on your config files
CacheManager::setup(array(
    "path" => "C:/tmp/", // or in windows "C:/tmp/"
));
CacheManager::CachingMethod("phpfastcache");

$start = microtime();

// In your class, function, you can call the Cache
$InstanceCache = CacheManager::Files();
// OR $InstanceCache = CacheManager::getInstance() <-- open examples/global.setup.php to see more

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page2";
$CachedString = $InstanceCache->get($key);

if (is_null($CachedString)) {
    $CachedString = "Files Cache --> Well done !";
    // Write products to Cache in 10 minutes with same keyword
    $InstanceCache->set($key, $CachedString, 600);

    echo "FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // ";
    echo $CachedString;

} else {
    echo "READ FROM CACHE // ";
    echo $CachedString;
}

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="/' . basename(__FILE__) . '">Reload</a>';
