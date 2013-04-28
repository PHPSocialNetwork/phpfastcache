More information at http://www.phpfastcache.com
E-Mail: khoaofgod@yahoo.com
---------------------------
*** CHMOD 0777 or any Writable Permission for caching.0777 file.
---------------------------
Reduce Database Calls

Your website have 10,000 visitors who are online, and your dynamic page have to send 10,000 same queries to database on every page load. 
With phpFastCache, your page only send 1 query to DB, and use the cache to serve 9,999 other visitors.

<?php
    // make sure YOU CHMOD 0777 for Caching.0777, and put it same PATH with php_fast_cache.php
    include("php_fast_cache.php");
    // try to get from Cache first.
    $products = phpFastCache::get("products_page");

    if($products == null) {
        $products = YOUR DB QUERIES || GET_PRODUCTS_FUNCTION;

        // set products in to cache in 600 seconds = 5 minutes
        phpFastCache::set("products_page",$products,600);
    }

    foreach($products as $product) {
        // Output Your Contents HERE
    }
?>

---------------------------
phpFastCache is Lightweight, Fastest & Security, use only 1 file to store all objects. 
You can hide the cache file by set phpFastCache::$path = "PATH/";

It is more simple by Set, Get, and automatic clean up cache. 
It is also save more CPU usage than using other file cache.

phpFastCache can't fast like A Memory Caching. Memory is always faster than file system, 
but phpFastCache is more easier than all other caching on integrated into your code. 
You won't need root permission, you don't need to edit php.ini, and will never worry about your server memory by caching again.
Are you thinking about DSO, CGI, FastCgi, suPHP? phpFastCache can run on all of them.

phpFastCache can run on shared hosting, VPS, Dedicated Server, 
and help you save lot of cost from upgrading your Web Server. 
phpFastCache help thousand of website already.

It's so simple to move from Server to Server. 
Just copy/download/upload your files! You're done! 
You won't need to worry about deleting thousand of cache files, or re-setup the server, and edit your php.ini again.

And It's open Source you can edit the source code by your own ^_^
Try it and you will love phpFastCache

Hey hey! Are you lazy to learn new thing? 
Dude, phpFastCache only uses 2 simple methods: set & get =.=!
---------------------------