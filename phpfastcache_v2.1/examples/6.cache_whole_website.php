<?php

/*
 *  PHP Cache whole web page : You can use phpFastCache to cache the whole webpage easy too.
 *  This is simple example, but in real code, you should split it to 2 files: cache_start.php and cache_end.php.
 *  The cache_start.php will store the beginning code until ob_start(); and the cache_end.php will start from GET HTML WEBPAGE.
 *   Then, your index.php will include cache_start.php on beginning and cache_end.php at the end of file.
 */

// use Files Cache for Whole Page / Widget
$cache = phpFastCache("files");

// keyword = Webpage_URL
$keyword = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING']);
$html = $cache->get($keyword);

if($html == null) {
    ob_start();
    /*
        ALL OF YOUR CODE GO HERE
        RENDER YOUR PAGE, DB QUERY, WHATEVER
    */

    // GET HTML WEBPAGE
    $html = ob_get_contents();
    // Save to Cache 30 minutes
    $cache->set($keyword,$html, 1800);
}

echo $html;