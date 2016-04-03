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

/**
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 */

// Include composer autoloader
require '../src/autoload.php';
// OR require_once("../src/autoload.php");

use phpFastCache\CacheManager;

// Setup File Path on your config files
CacheManager::setup(array(
   // "path" => sys_get_temp_dir(), // or in windows "C:/tmp/"
));
// our unique method of caching, faster than traditional caching which shared everywhere on internet like 7-10 times
// reduce high load CPU, reduce I/O from files open
// reduce missing hits of memcache, reduce connection to redis and others caches
// Accepted value: "normal" < "memory" < "phpfastcache"
CacheManager::CachingMethod("phpfastcache");

// In your class, function, you can call the Cache
$InstanceCache = CacheManager::Files();
// OR $InstanceCache = CacheManager::getInstance() <-- open examples/global.setup.php to see more

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
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

// Testing Functions
require_once __DIR__."/TestingFunctions.php";

