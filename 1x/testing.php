<?php
/*
 * A Testing File for you can try testing & debugging phpFastCache;
 * Just run on your webserver http://localhost/webserver/testing/index.php
 * Any question please skype: khoaofgod or email khoaofgod@yahoo.com
 * More information at http://www.phpfastcache.com
 */

include("php_fast_cache.php");
$caching = array("auto","files","pdo","mpdo","xcache","apc","memcache","memcached","wincache");
$tmp = new phpFastCache();
// data use for caching ^ testing
$data = array(
    1,
    "abc hello world",
    "",
    null,
    0,
    array(
        1,2,3,4,"string",null,"","GOT IT","KEY"=> "VALUE", "N" => null, "E" => "", "X" => -1
    ),

);
// $data = array(null,null,"",null);
echo "Try get Server information ... Take 10 - 15 seconds to detect ...<br>";
phpFastCache::debug(phpFastCache::systemInfo());
echo "<b>Data will be testing - Included NULL and Emptry String</b>";
phpFastCache::debug($data);
$c = array("red","blue","green","orange","black","red","blue","green","orange","black");
$xx=0;
foreach($caching as $st) {
    $dem=0;

    $color = $c[$xx]; $xx++;
    phpFastCache::$storage=$st;
    echo "<font color=$color >
    ------------------------------------------------- <br>
    Caching = $st <br>";
    if((isset(phpFastCache::$sys['drivers'][$st]) && phpFastCache::$sys['drivers'][$st] == true) || $st=="auto") {
        foreach($data as $string) {
            $dem++;
            echo "SET $dem --> ";
            phpFastCache::set("A".$dem,$string);
            echo "GET $dem = ";
            $x = phpFastCache::get("A".$dem);

            if(is_array($x)) {
                print_r($x);
            } else {
                echo $x;
                if(is_numeric($x)) {
                    phpFastCache::increment("A".$dem);
                    phpFastCache::increment("A".$dem);
                    phpFastCache::increment("A".$dem);
                    $x = phpFastCache::get("A".$dem);
                    echo " Increase to ".$x;

                    phpFastCache::decrement("A".$dem);
                    phpFastCache::decrement("A".$dem);
                    phpFastCache::decrement("A".$dem);
                    $x = phpFastCache::get("A".$dem);
                    echo " Decrease to ".$x;
                }
            }

            phpFastCache::delete("A".$dem);
            echo " || Finished Testing || Deleted ";
            echo "<br>";
        }
    } else {
        echo " <-- NOT SUPPORTED ON THIS SERVER WITH DEFAULT CONFIG 127.0.0.1 <br>";
    }
    echo "</font>";


}

echo "<hr>";
phpinfo();


