<?php

/*
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 */

// Require Library
require_once("../phpfastcache.php");

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


