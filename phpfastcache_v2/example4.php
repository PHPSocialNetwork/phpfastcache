<?php

/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */

/*
 * This is Document
 * Read comments and learns
 */
include("phpfastcache/phpfastcache.php");

// create new instance, this instance will be destroy after function or page finished
$cache = new phpFastCache("auto");
$cache = new phpFastCache("memcache");
$cache = new phpFastCache("sqlite");

// create instance, this instance will "not" be destroy after your function finished,
// It keeps running until your php "page finished"

$cache = phpFastCache("auto");
$cache = phpFastCache();

$cache = __c("auto");
$cache = __c();

/*
 * How to get from Cache ?
 */
$data = $cache->get("keyword1");
$data = $cache->keyword1;

$data = __c()->get("keyword3");
$data = __c()->keyword4;

$data = $cache->memcache->keyword5;
$data = __c()->files->get("keyword6");

/*
 * How to Write to Cache
 */

$cache->set("keyword1","data",300);
$cache->keywrod2 = array($list_product, 3600);

__c()->set("keyword3", $products, 3600);
__c("files")->keyword4 = array("something here", 3600*24*7);







