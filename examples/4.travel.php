<?php

/*
 * One of Great things about phpFastCache is Traveling
 * You can travel between all caching drivers
 */

/*
 * Travel by $cache
 */

$cache = phpFastCache();


// Now, this is magic
$product = $cache->get("keyword");
$product = $cache->apc->get("keyword");
$product = $cache->memcached->get("keyword");

$cache->files->set("keyword","array | object",300);
$cache->files->keyword = array("array | object", 300);

/*
 * phpFastCache is free traveling
 */

$cache = phpFastCache("files");

$cache->memcache->keyword = array("data",300);
$cache->sqlite->set("keyword","data",300);

$product = $cache->apc->get("keyword");
$product = $cache->files->keyword;

/*
 * We use Files cache for widget caching
 * Memory caching like APC, Memcached for DB Caching
 */