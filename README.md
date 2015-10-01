[![Code Climate](https://codeclimate.com/github/khoaofgod/phpfastcache/badges/gpa.svg)](https://codeclimate.com/github/khoaofgod/phpfastcache)

---------------------------
Simple Yet Powerful PHP Caching Class
---------------------------
More information at http://www.phpfastcache.com
One Class uses for All Cache. You don't need to rewrite your code many times again.

Supported: Redis, Predis, Cookie, Files, MemCache, MemCached, APC, WinCache, X-Cache, PDO with SQLite

---------------------------
Reduce Database Calls

Your website have 10,000 visitors who are online, and your dynamic page have to send 10,000 same queries to database on every page load.
With phpFastCache, your page only send 1 query to DB, and use the cache to serve 9,999 other visitors.

```php
<?php
/*
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 */

// Require Library
// Keep it Auto or setup it as "files","sqlite","wincache" ,"apc","memcache","memcached", "xcache"
require_once("phpfastcache.php");
phpFastCache::setup("storage","auto");
// phpFastCache::setup("storage","files");
// OR open phpfastcache.php and edit your config value there

// simple Caching with:
$cache = phpFastCache();
// $cache = phpFastCache("redis");

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
```
---------------------------
```php
<?php

/*
 * List of function and example
 */
require_once("phpfastcache.php");
$cache = phpFastCache();

// Write into cache
$cache->set("keyword", "data | array | object", 300);
$cache->set("keyword", "data | array | object"); // <-- Non-Expired Objects without Time, until you delete the cache.

// Read from Cache | return "null" or "data"
$data = $cache->get("keyword");
echo $data;

// Temporary disabled phpFastCache
phpFastCache::$disabled = true;
$data = $cache->get("keyword");
echo $data; // ALWAYS RETURN NULL;

// Read object information | value | time from cache
$object = $cache->getInfo("keyword");
print_r($object);

// Delete from cache
$cache->delete("keyword");

// Clean up all cache
$cache->clean();

// Stats
$array = $cache->stats();
print_r($array);

// Increase and Decrease Cache value - Return  true | false
$cache->increment("keyword", 1);
$cache->decrement("keyword", 1);

// Extend expiring time - Return true | false;
$cache->touch("keyword", 1000);

// Check Existing or not - Return true | false;
$cache->isExisting("keyword");

// Get & Set Multiple Items
// Same as above, but input is array();

$list = $cache->getMulti(array("key1","key2","key3"));

$list = $cache->getInfoMulti(array("key1","key2","key3"));

$cache->setMulti(array("key1","value1", 300),
    array("key2","value2", 600),
    array("key3","value3", 1800));

$cache->deleteMulti(array("key1","key2","key3"));

$cache->isExistingMulti(array("key1","key2","key3"));

$cache->touchMulti(array(
                    array("key", 300),
                    array("key2", 400),
                   ));

$cache->incrementMulti(array(
                        array("key", 1),
                        array("key2", 2),
                    ));

$cache->decrementMulti(array(
                        array("key", 1),
                        array("key2", 2),
                    ));

```
