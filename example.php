<?php
include("php_fast_cache.php");
phpFastCache::$storage = "auto";

// ready ?
// check in case first

$content = phpFastCache::get("keyword1");

if($content == null) {
    // for testing
    echo "This is not caching, page is render with lot queires and slow speed <br>";
    // do what you want, like get content from cURL | API | mySQL Query and return result to $content
    $content = file_get_contents("http://www.phpfastcache.com/testing.php");

    // rewrite cache for other request in 5 seconds
    phpFastCache::set("keyword1",$content,5);
} else {
    // use cache
    // node
    echo "THIS TIME USE CACHE, FAST! <br>";
}

echo "TRY F5 to refesh the page to see new SPEED with Cache!<br>";
echo $content;

