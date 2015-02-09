<?php

/*
 * shortcut! to make it faster!
 * phpFastCache can use 2 functions:
 * __c() and phpFastCache() at any where without limit
 *
 * Example 1
 */

require_once("../phpfastcache.php");

// get from cache
$products = __c()->my_products;

if($products == null) {
    $products = "ARRAY | OBJECT | FUNCTION GET PRODUCTS";
    // write to cache;
    __c()->my_products = array($products, 300);
}

echo $products;

/*
 * Example 2 - Short Cut
 */

$products = __c("files")->get("keyword");

// Write to Files Cache 24 hours;
__c("files")->set("keyword","data | something | array | object", 3600*24);

