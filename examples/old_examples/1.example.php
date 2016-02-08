<?php
/**
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpFastCache.com
 */

use phpFastCache\Core\InstanceManager;

// Include composer autoloader
require '../vendor/autoload.php';

$cache = InstanceManager::getInstance();

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$products = $cache->get($key);

if (is_null($products)) {
    $products = "DB QUERIES | FUNCTION_GET_PRODUCTS | ARRAY | STRING | OBJECTS";
    // Write products to Cache in 10 minutes with same keyword
    $cache->set($key, $products, 600);

    echo " --> NO CACHE ---> DB | Func | API RUN FIRST TIME ---> ";

} else {
    echo " --> USE CACHE --> SERV 10,000+ Visitors FROM CACHE ---> ";
}

/**
 * use your products here or return it;
 */
echo $products;



