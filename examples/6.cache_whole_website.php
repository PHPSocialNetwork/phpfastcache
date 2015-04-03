<?php

/*
 *  PHP Cache whole web page : You can use phpFastCache to cache the whole webpage easy too.
 *  This is simple example, but in real code, you should split it to 2 files: cache_start.php and cache_end.php.
 *  The cache_start.php will store the beginning code until ob_start(); and the cache_end.php will start from GET HTML WEBPAGE.
 *   Then, your index.php will include cache_start.php on beginning and cache_end.php at the end of file.
 */

// use Files Cache for Whole Page / Widget

// keyword = Webpage_URL
$keyword_webpage = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING']);

$html = null;
$caching = true;

// no caching for some special page;
// $_SERVER['REQUEST_URI'];
if(strpos($_SERVER['REQUESR_URI'],"contact") !== false || strpos($_SERVER['REQUESR_URI'],"login") !== false  || strpos($_SERVER['REQUESR_URI'],"register") !== false  ) {
    $caching = false;
}

// no caching if $_POST
if(isset($_POST)) {
    $caching = false;
}

// no caching if have some $_GET ?string=var
if(isset($_GET['somevar']) && $_GET['somevar'] == "something") {
    $caching = false;
}

// No caching for logined user
// can use with $_COOKIE AND $_SESSION
if(isset($_SESSION) && $_SESSION['logined'] === true) {
    $caching = false;
}

// ONLY ACCESS CACHE IF $CACHE = TRUE
if($caching === true ) {
    $html = __c("files")->get($keyword_webpage);
}


if($html == null) {
    ob_start();
    /*
        ALL OF YOUR CODE GO HERE
        RENDER YOUR PAGE, DB QUERY, WHATEVER
    */
    echo " WEBSITE HTML HERE ";
    echo " MY WEB SITE CONTENTS IS HERE";
    echo " ENDING WEBSITE CONTENT";

    // GET HTML CONTENT
    $html = ob_get_contents();
    // Save to Cache 30 minutes
    __c("files")->set($keyword_webpage,$html, 1800);
}

echo $html;