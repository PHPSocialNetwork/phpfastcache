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
$products = $cache->get("product_page_keyword");

if($products == null) {
    $products = "DB QUERIES | FUNCTION_GET_PRODUCTS | ARRAY | STRING | OBJECTS";
    // Write products to Cache in 10 second with same keyword
    // 600 = 60 x 10 = 10 minutes
    $cache->set("product_page_keyword",$products , 10);
    echo " THIS TIME RUN WITHOUT CACHE <br> ";
} else {
    echo " USE CACHE, NO QUERY CALL <br>  ";
}

// use your products here or return it;
echo $products;


