<?php

/**
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 */

	// In your config files
require_once("../../src/autoload.php");

	// now it's time to call the cache "anywhere" on your project

	$cache = phpFastCache("redis");

if ($cache->fallback === true) {
    echo " USE BACK UP DRIVER = " . phpFastCache::$config[ 'fallback' ] . " <br>";
} else {
    echo ' DRIVER IS GOOD <br>';
}

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page2";
$products = $cache->get($key);

if (is_null($products)) {
    $products = "DB QUERIES | FUNCTION_GET_PRODUCTS | ARRAY | STRING | OBJECTS";
    // Write products to Cache in 10 minutes with same keyword
    $cache->set($key, $products, 600);

    echo " --> NO CACHE ---> DB | Func | API RUN FIRST TIME ---> ";

} else {
    echo " --> USE CACHE --> SERV 10,000+ Visitors FROM CACHE ---> ";
}

// use your products here or return it;
echo "Products = " . $products;



