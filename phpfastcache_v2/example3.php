<?php
/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */

// required Lib
include("phpfastcache/phpfastcache.php");

function myfunction1() {
    // WARNING: $cache = phpFastCache();
    // --> "NOT" $cache = new php..Blabla();
    $cache = phpFastCache();
    $data = $cache->get("hello");
    if($data == null) {
        $data = "ENJOY INSTANT";
        $cache->set("hello",$data,10);
    }
}

function myfunction2() {
    // WARNING: $cache = phpFastCache();
    // --> "NOT" $cache = new php..Blabla();
    $cache = phpFastCache();
    /*
     *  Because you created first instant on function 1, so at this function 2, it used the existing once, and didn't create new one
     *  That's mean: if in function1() ; you use $cache = phpFastCache("memcache"); the $cache will connect to Memcache Server
     *  Then, in function2() , you use $cache = phpFastCache("memcache"); it's gonna use existing once, and it will not create new instant to connect to Memcache Server again to save memory.
     *  The instances will not be destroy until php stopped, for you can reuse it all the time in your scripts.
     */

    $data = $cache->get("hello2");
    if($data == null) {
        $data = "THIS CACHE IS NOT CREATE NEW INSTANT. IT USES THE EXISTING ONE TO SAVE MEMORY";
        $cache->set("hello2",$data,10);
    }

}

myfunction1();
myfunction2();





