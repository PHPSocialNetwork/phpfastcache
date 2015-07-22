<?php

/*
 * Here is how to setup and work on phpFastCache fast!
 */

require_once("phpfastcache.php");
// auto, files, sqlite, xcache, memcache, apc, memcached, wincache
phpFastCache::setup("storage","auto");

// SET a Data into Cache
__c()->set("keyword", "array|object|string|data", $time_in_second);

// GET a Data from Cache
$data = __c()->get("keyword");

$object = __c()->getInfo("keyword"); // ARRAY

// Others Funtions
__c()->delete("keyword");
__c()->increment("keyword", $step = 1); // TRUE | FALSE
__c()->decrement("keyword", $step = 1); // TRUE | FALSE
__c()->touch("keyword", $more_time_in_second); // TRUE | FALSE
__c()->clean();
__c()->stats(); // ARRAY
__c()->isExisting("keyword"); // TRUE | FALSE

// Direct Keyword SET & GET
__c()->keyword = array("array|object|string|data", $time_in_second);
$data = __c()->keyword;