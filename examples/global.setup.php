<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

/**
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
// In your project main config file, you can setup the storage
use phpFastCache\CacheManager;

// Include composer autoloader
require '../vendor/autoload.php';
// OR require_once("../src/autoload.php");

// Setup File Path on your config files
$config = array(
    "storage"   =>  "files", // ssdb, files, xcache, sqlite, memcache, memcached, redis, predis, apc, cookie, wincache
    "path" => sys_get_temp_dir(), // or in Windows: "C:/tmp/" or "/path/to/your/cache/folder/"
    "allow_search"  =>   false, // change to true to turn on search method
    "overwrite"     =>  "", // when your memcache server broken, simple put "files" here, and it will overwrite everything to files cache until u fixed ur server
);
CacheManager::setup($config);

// In your functions / class / anywhere you want to use cache:
// this one will load the storage on your setup
// so, when your files or memcached get problems, you go to Config and change to redis, ssdb.. etc, and you don't need to modifile your code;
$cache = CacheManager::getInstance(); // return your setup storage

// OR you can call any type of caching
// However if your Memcached crash, then you have to modifile your code ::Memcached() to ::Files() , etc...
$cache_memcache = CacheManager::Memcached(); // return memcache
$cache_apc = CacheManager::Apc(); // return apc
$cache_redis = CacheManager::Redis(); // return redis


// You can also pass the setup directly to cache
$cache = CacheManager::Files($config);
$cache = CacheManager::getInstance("Files",$config);

/**************************************************
 * Overwrite Caching Driver
 **************************************************/

// We have a config called "overwrite" , it will force everything to 1 kind of Cache for you don't need to modifile your code
// Setup File Path on your config files
$config = array(
    "storage"   =>  "memcached", // ssdb, files, xcache, sqlite, memcache, memcached, redis, predis, apc, cookie, wincache
    "overwrite"     =>  "files", // Any caching will be overwrite to files cache until your fix your server and removed this line
    "path" => sys_get_temp_dir() // or in Windows: "C:/tmp/"
);
CacheManager::setup($config);

$cache = CacheManager::getInstance(); // return Files Cache
$cache_memcache = CacheManager::Memcached();  // return Files Cache
$cache_apc = CacheManager::Apc();  // return Files Cache
$cache_redis = CacheManager::Redis();  // return Files Cache






