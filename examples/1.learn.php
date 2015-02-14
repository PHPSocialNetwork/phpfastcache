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


/* 
	#1
*/

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


/* 
	#2
*/
$test = $cache->check("test");

if($test == null) {

	try {
		// 1. exec~ db query to another server

		//throw new Exception('the second server does not respond');
		$test = 'query result';
		$cache->set('test', $test, 15);

		echo $test.' <- Load Cached data';

	} catch (Exception $e) {
		$test = $cache->get('test',array('check_expiry' => false));
		echo $test . ' <- ' .$e->getMessage();
	}

} else {
	echo $test.' <- Load Cached data';
}
