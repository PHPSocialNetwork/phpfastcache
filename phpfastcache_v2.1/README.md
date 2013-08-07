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

// in your config files
include("phpfastcache/phpfastcache.php");
// auto | memcache | files ...etc. Will be default for $cache = __c();
phpFastCache::$storage = "auto";

$cache1 = phpFastCache("files");
$cache1->option("path","/PATH/TO/SOME_WHERE/STORE_FILES/");

$cache2 = __c("memcache");
$server = array(array("127.0.0.1",11211,100), array("128.5.1.3",11215,80));
$cache2->option("server", $server);

$cache3 = new phpFastCache("apc");

// How to Write?
$cache1->set("keyword1", "string|number|array|object", 300);
$cache2->keyword2 = array("something here", 600);
__c()->keyword3 = array("array|object", 3600*24);

// How to Read?
$data = $cache1->get("keyword1");
$data = $cache2->keyword2;
$data = __c()->keyword3;
$data = __c()->get("keyword4");

// Free to Travel between any caching methods

$cache1 = phpFastCache("files");
$cache1->set("keyword1", $value, $time);
$cache1->memcache->set("keyword1", $value, $time);
$cache1->apc->set("whatever", $value, 300);

$cache2 = __c("apc");
$cache2->keyword1 = array("so cool", 300);
$cache2->files->keyword1 = array("Oh yeah!", 600);

$data = __c("memcache")->get("keyword1");
$data = __c("files")->get("keyword2");
$data = __c()->keyword3;

// Multiple ? No Problem

$list = $cache1->getMulti(array("key1","key2","key3"));
$cache2->setMulti(array("key1","value1", 300),
    array("key2","value2", 600),
    array("key3","value3", 1800),
                      );

$list = $cache1->apc->getMulti(array("key1","key2","key3"));
__c()->memcache->getMulti(array("a","b","c"));

// Others
$cache->delete("keyword");
$cache->increment("keyword", $step = 1);
$cache->decrement("keyword", $step = 1);
$cache->clean();
$cache->stats();
$cache->isExisting("keyword");
````