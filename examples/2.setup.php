<?php

/*
 * Learn how to setup phpFastCache ?
 */

require_once("../phpfastcache.php");

/*
 * Now this is Optional Config setup for default phpFastCache
 */

$config = array(
        /*
         * Default storage
         * if you set this storage => "files", then $cache = phpFastCache(); <-- will be files cache
         */
        "storage"   =>  "auto", // files, sqlite, auto, apc, wincache, xcache, memcache, memcached,

        /*
         * Default Path for Cache on HDD
         * Use full PATH like /home/username/cache
         * Keep it blank "", it will automatic setup for you
         */
        "path"      =>  "" , // default path for files
        "securityKey"   =>  "", // default will good. It will create a path by PATH/securityKey

        /*
         * FallBack Driver
         * Example, in your code, you use memcached, apc..etc, but when you moved your web hosting
         * The new hosting don't have memcached, or apc. What you do? Set fallback that driver to other driver.
         */
        "fallback"  =>  "files",

        /*
         * .htaccess protect
         * default will be  true
         */
        "htaccess"  =>  true,

        /*
         * Default Memcache Server for all $cache = phpFastCache("memcache");
         */
        "memcache"        =>  array(
                array("127.0.0.1",11211,1),
            //  array("new.host.ip",11211,1),
        ),
		// Default server for redis
		"redis"         =>  array("127.0.0.1",6379),


);

phpFastCache::setup($config);

// OR

phpFastCache::setup("storage","files");
phpFastCache::setup("path", dirname(__FILE__));

/*
 * End Optional Config
 */

$cache = phpFastCache(); // this will be $config['storage'] up there;
$cache2 = phpFastCache("memcache"); // this will use memcache
$cache3 = new phpFastCache("apc"); // this will use apc

$products = $cache->my_products;
if($products == null) {
    $products = "object | array | function_get_products";
    // write to cache
    $cache->my_products = array($products, 600);
}

echo $products;