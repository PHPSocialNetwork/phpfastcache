<?php
    // MAKE SURE you CHMOD 0777 for caching.077
    include("php_fast_cache.php");


    // Example 1

    $products = phpFastCache::get("page1_anything");
    if($products == null) {
        $products = array(
                "A" => "Apple",
                "B" => "Orange"
        );
        // write cache 10 seconds
        phpFastCache::set("page1_anything",$products,10);

        echo "NO CACHE --- ";
    } else {
        echo "USE CACHE -- SAVE CPU";
    }

    print_r($products);

    echo "<br>";

    // Example 2
    /*
    phpFastCache::$storage = "auto"; // auto create files cache, enable multi files caching
    phpFastCache::$autosize = 30; // 30 megabytes max per file.
    phpFastCache::$path = "";   // PATH TO CACHE OR LEAVE BLANK

    $products = phpFastCache::get("page1_anything");
    if($products == null) {
        $products = array(
            "A" => "Apple",
            "B" => "Orange"
        );
        // write cache 10 seconds
        phpFastCache::set("page1_anything",$products,10);

        echo "NO CACHE --- ";
    } else {
        echo "USE CACHE -- SAVE CPU";
    }

    print_r($products);
    */



    // Example 3
    /*
    phpFastCache::$storage = "auto"; // auto create files cache, enable multi files caching
    phpFastCache::$autosize = 30; // 30 megabytes max per file.
    phpFastCache::$path = "";   // PATH TO CACHE OR LEAVE BLANK

    // use array("cachefile" => "itemname");

    $products = phpFastCache::get(array("categories" => "page1_anything"));
    if($products == null) {
        $products = array(
            "A" => "Apple",
            "B" => "Orange"
        );
        // write cache 10 seconds
        phpFastCache::set(array("categories" => "page1_anything"),$products,10);

        echo "NO CACHE --- ";
    } else {
        echo "USE CACHE -- SAVE CPU";
    }

    print_r($products);
    */

?>