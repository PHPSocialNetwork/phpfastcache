<?php
/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */

// required Lib
include("phpfastcache/phpfastcache.php");

// auto, files, sqlite, memcache, memcached, xcache, wincache, apc
/*
 * Use new phpFastCache(); <-- same as Auto
 */
$cache = new phpFastCache();
phpFastCache::$storage = "files";
/*
 * From NOW, every time you use $cache = phpFastCache(); in any function, it will use files method.
 */

// try get in cache first
$products = $cache->get("my_products");

if($products == null) {
    echo " NO CACHE HERE, I DO SOME FUNCTIONS | PRODUCTS = ";
    // $products = my_functions_get_products() || OR GET PRODUCTS FROM SQL;
    $products = array(1,2,3,4,5,6);
    // write products into cache for serving others visitors
    // time in second
    $cache->set("my_products",$products,3);

} else {
    echo " THIS TIME USED CACHE, NO QUERY, NO FUNCTIONS TO GET PRODUCTS = ";
}

// RENDER YOUR PRODUCTS | PAGE
echo "<pre>";
print_r($products);

echo "<h2>Press F5, Refesh to see other result!</h2>";



