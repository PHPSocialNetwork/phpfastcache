<?php
use Phpfastcache\core\InstanceManager;

// Include composer autoloader
require '../vendor/autoload.php';

$cache = InstanceManager::getInstance();


// Now, this is magic
$product = $cache->get("keyword");
$product = $cache->apc->get("keyword");
$product = $cache->memcached->get("keyword");

$cache->files->set("keyword", "array | object", 300);
$cache->files->keyword = array("array | object", 300);

/*
 * phpFastCache is free traveling
 */

$cache = InstanceManager::getInstance("files");

$cache->memcache->keyword = array("data", 300);
$cache->sqlite->set("keyword", "data", 300);

$product = $cache->apc->get("keyword");
$product = $cache->files->keyword;