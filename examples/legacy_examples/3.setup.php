<?php
// In your config files
require_once("../../src/autoload.php");

// now it's time to call the cache "anywhere" on your project

$cache = phpFastCache();

/**
 * Now this is Optional Config setup for default phpFastCache
 */

$config = array(
  "storage" => "auto", // auto, files, sqlite, apc, cookie, memcache, memcached, predis, redis, wincache, xcache
  "default_chmod" => 0777, // For security, please use 0666 for module and 0644 for cgi.


    /*
     * OTHERS
     */

    // create .htaccess to protect cache folder
    // By default the cache folder will try to create itself outside your public_html.
    // However an htaccess also created in case.
  "htaccess" => true,

    // path to cache folder, leave it blank for auto detect
  "path" => "",
  "securityKey" => "auto", // auto will use domain name, set it to 1 string if you use alias domain name

    // MEMCACHE

  "memcache" => array(
    array("127.0.0.1", 11211, 1),
      //  array("new.host.ip",11211,1),
  ),

    // REDIS
  "redis" => array(
    "host" => "127.0.0.1",
    "port" => "",
    "password" => "",
    "database" => "",
    "timeout" => "",
  ),

  "extensions" => array(),


    /*
     * Fall back when old driver is not support
     */
  "fallback" => "files",
);

phpFastCache::setup($config);

// OR

phpFastCache::setup("storage", "files");
phpFastCache::setup("path", __DIR__);

/*
 * End Optional Config
 */

$config = array(
    // ALL OF YOUR CONFIG HERE
    // except storage
);

$cache = CacheManager::getInstance("files", $config); // this will be $config['storage'] up there;

// changing config example
$cache->setup("path", "new_path");


$cache2 = CacheManager::getInstance("memcache"); // this will use memcache
$server = array(
  array("127.0.0.1", 11211, 1),
);
$cache2->setup("memcache", $server);