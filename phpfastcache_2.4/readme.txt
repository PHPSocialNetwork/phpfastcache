Please read examples/1.learn.php


Simple Yet Powerful PHP Caching Class
---------------------------
More information at http://www.phpfastcache.com
One Class uses for All Cache. You don't need to rewrite your code many times again.
Supported: Files, MemCache, MemCached, APC, WinCache, X-Cache, PDO with SQLite
---------------------------
Reduce Database Calls

Your website have 10,000 visitors who are online, and your dynamic page have to send 10,000 same queries to database on every page load.
With phpFastCache, your page only send 1 query to DB, and use the cache to serve 9,999 other visitors.

<?php
/*
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 */

// Require Library
// Keep it Auto or setup it as "files","sqlite","wincache" ,"apc","memcache","memcached", "xcache"
require_once("../phpfastcache/phpfastcache.php");
phpFastCache::setup("storage","auto");

// simple Caching with:
$cache = phpFastCache();

// Try to get $products from Caching First
// product_page is "identity keyword";
$products = $cache->get("product_page");

if($products == null) {
    $products = "DB QUERIES | FUNCTION_GET_PRODUCTS | ARRAY | STRING | OBJECTS";
    // Write products to Cache in 10 minutes with same keyword
    $cache->set("product_page",$products , 600);
}

// use your products here or return it;
echo $products;

?>