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