<?php
/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */

// required Lib
include("phpfastcache/phpfastcache.php");

// auto, files, sqlite, memcache, memcached, xcache, wincache, apc

// try get in cache first
$products = __c()->get("my_products_page");

if($products == null) {
    echo " NO CACHE HERE, I DO SOME FUNCTIONS | PRODUCTS = ";
    // $products = my_functions_get_products() || OR GET PRODUCTS FROM SQL;
    $products = "A | B | C | ARRAY | OBJECT | 1 | 2 | 3 | .. Etc";
    // write products into cache for 10 minutes and use cache to serv others visitors
    // 600 seconds = 10 minutes
    __c()->my_products_page = array($products,3);

} else {
    echo " THIS TIME USED CACHE, NO QUERY, NO FUNCTIONS TO GET PRODUCTS = ";
}

// RENDER YOUR PRODUCTS | PAGE
echo $products;

/*
 * foreach($products as blablabla)
 * Render your page here
 * This method is very good to cache DB Query, Save a lot of CPU
 */


