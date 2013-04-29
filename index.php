<?php

mb_internal_encoding("utf8");
include("php_fast_cache.php");




$cache = 1;
echo phpFastCache::get("name1");
echo "-->";
phpFastCache::set(array("name1"),$cache);
// echo phpFastCache::increment("name1");
/*
// phpFastCache::set(array("name1"),$cache);
echo "<br>";
print_r(phpFastCache::get("name1"));
echo "-->";
echo phpFastCache::increment("name1");
echo "-->";
echo phpFastCache::get("name1");
*/
?>