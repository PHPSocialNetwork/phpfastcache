Simple Yet Powerful PHP Caching Class
---------------------------
More information at http://www.phpfastcache.com
One Class uses for All Cache. You don't need to rewrite your code many times again.
Supported: Files, MemCache, MemCached, APC, WinCache, X-Cache, PDO with SQLite
---------------------------
Reduce Database Calls

Your website have 10,000 visitors who are online, and your dynamic page have to send 10,000 same queries to database on every page load.
With phpFastCache, your page only send 1 query to DB, and use the cache to serve 9,999 other visitors.

```php
<?php
    include("php_fast_cache.php");
    phpFastCache::$storage = "auto";

    // try to get from Cache first.
    $products = phpFastCache::get("products_page");


    if($products == null) {
        $products = YOUR DB QUERIES || GET_PRODUCTS_FUNCTION;

        // set products in to cache in 600 seconds = 10 minutes
        phpFastCache::set("products_page",$products,600);
    }

    foreach($products as $product) {
        // Output Your Contents HERE
    }
```
---------------------------
```php
include("php_fast_cache.php");
/*
* Optional Config || You can skip these config, everything is Automatic ^_^
* -----------------------------
* phpFastCache::$storage = "auto"; // auto | pdo | mpdo | files | memcache | memcached | apc | wincache | xcache
* -----------------------------
* Just Remember Only 2 Functions: Set & Get.
* SET Functions
* phpFastCache::set("item_name", $value, second);
* OR
* phpFastCache::set("item_name", $value, second, true); // skip if existing
*
* phpFastCache::set(array("apc" => "item_name"), $value, 600);
* phpFastCache::set(array("files" => "item_name"), $value, 600);
* phpFastCache::set(array("memcached" => "item_name"), $value, 600);
* phpFastCache::set(array("pdo" => "item_name"), $value, 600);
*
* phpFastCache::set(array("files/2013/categories" => "item_name"), $value, 600);
* phpFastCache::set(array("db1" => "item_name"), $value, 600);
* phpFastCache::set(array("db2" => "item_name"), $value, 600);
* phpFastCache::set(array("db3" => "item_name"), $value, 600);
* phpFastCache::set(array("prefix" => "item_name"), $value, 600);
*
* GET FUNCTIONS ( return NULL or Value of item )
* phpFastCache::get("item_name");
* phpFastCache::get(array("prefix" => "item_name"));
*
* -----------------------------
* -----------------------------
* -----------------------------
* -----------------------------
*
* Others Functions if you are interesting
* item_name can be string or array("where" => "name");
*
* phpFastCache::delete("item_name");
* phpFastCache::cleanup("item_name");
* phpFastCache::stats();
* phpFastCache::increment("item_name", $step = 1);
* phpFastCache::decrement("item_name", $step = 1);
* phpFastCache::exists("item_name");
* print_r(phpFastCache::systemInfo());
*
* -----------------------------
* -----------------------------
* -----------------------------
* phpFastCache::setMulti(array(
*                                   array("a","hello",600),
*                           array("b","value")),
*                       500,
*                       false);
*
* phpFastCache::setMulti(array(
*                           array("a","hello"),
*                           array("b","value")),
*                        3600*24);
*
* phpFastCache::setMulti(array(
*                           array("files" => "a", "data", 3600*24),
*                           array("apc"   => "b", "hello world", 500),
*                           array("c", "array|object|info"));
*
* phpFastCache::getMulti(array("a","b","c"));
* phpFastCache::getMulti(array("files"  =>  "a", "apc"  => "b", "c"));
*
* phpFastCache::deleteMulti(array("a","b","c"));
* phpFastCache::deleteMulti(array("files"   =>  "a", "memcached"    => "b", "c"));
*
*
* ----------------------------
* Custom PATH & Security
* phpFastCache::$path = "/PATH/FOR_PDO_FILES/"; need chmod 0777 or writable mode
* phpFastCache::$securityKey = "cache.storage"; default cache folder name;
* phpFastCache::$files_cleanup_after = 1; hour collect expired files
* phpFastCache::$server = array(
*                   array("localhost",11211,30),
*                   array("localhost",11211,70)
*               );  <-- Memcache Server
* phpFastCache::$useTmpCache = true; // faster checking cache for LOOP Only. If you don't use LOOP, don't spend more memory.
* Example:
* WHILE() {
*   $cache = phpFastCache::get("name");
* }
* LOOP() {
*   $cache = phpFastCache::get("name");
* }
*    --> THE NAME maybe duplicated some times, if you set $useTmpCache it will check from $Tmp first before IT connect to Cache Memory or Open Files to check.
*        Will be faster a little bit if you know what you are doing.
* -----------------------------
* -----------------------------
* -----------------------------
* -----------------------------
*/
```
---------------------------
E-Mail: khoaofgod@yahoo.com
