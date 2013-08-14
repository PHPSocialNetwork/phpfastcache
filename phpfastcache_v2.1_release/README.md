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
    include("phpfastcache/phpfastcache.php");
    phpFastCache::$storage = "auto";

    // try to get from Cache first.
    $products = __c()->get("products_page");


    if($products == null) {
        $products = YOUR DB QUERIES || GET_PRODUCTS_FUNCTION;

        // set products in to cache in 600 seconds = 10 minutes
        __c()->set("products_page",$products,600);
    }

    foreach($products as $product) {
        // Output Your Contents HERE
    }
```
---------------------------
```php
<?php
/*
 * List of function and example
 */
require_once("../phpfastcache/phpfastcache.php");
$cache = phpFastCache();

// Write into cache
$cache->set("keyword", "data | array | object", 300);

// Read from Cache | return null or data
$data = $cache->get("keyword");
echo $data;

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





````