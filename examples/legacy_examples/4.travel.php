<?php
use phpFastCache\CacheManager;

// Include composer autoloader
require_once("../../src/autoload.php");

$cache = CacheManager::getInstance();


// Now, this is magic
$product = $cache->get("keyword");
$product = $cache->apc->get("keyword");
$product = $cache->memcached->get("keyword");

$cache->files->set("keyword", "array | object", 300);
$cache->files->keyword = array("array | object", 300);

/*
 * phpFastCache is free traveling
 */

$cache = CacheManager::getInstance("files");

$cache->memcache->keyword = array("data", 300);
$cache->sqlite->set("keyword", "data", 300);

$product = $cache->apc->get("keyword");
$product = $cache->files->keyword;