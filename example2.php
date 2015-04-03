<?php

/*
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 */

// Require Library
require_once("phpfastcache.php");

// simple Caching with:
$cache = phpFastCache("redis");

if($cache->fallback === true) {
	echo " USE BACK UP DRIVER = ".phpFastCache::$config['fallback']." <br>";
} else {
	echo ' DRIVER IS GOOD <br>';
}



// Try to get $products from Caching First
// product_page is "identity keyword";
$products = $cache->get("product_page2");

if($products == null) {
	$products = "DB QUERIES | FUNCTION_GET_PRODUCTS | ARRAY | STRING | OBJECTS";
	// Write products to Cache in 10 minutes with same keyword
	$cache->set("product_page2",$products , 2);

	echo " --> NO CACHE ---> DB | Func | API RUN FIRST TIME ---> ";

} else {
	echo " --> USE CACHE --> SERV 10,000+ Visitors FROM CACHE ---> ";
}

// use your products here or return it;
echo "Products = ".$products;



