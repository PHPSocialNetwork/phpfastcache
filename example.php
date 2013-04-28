<?php
    // MAKE SURE you CHMOD 0777 for caching.077
    include("php_fast_cache.php");

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


?>