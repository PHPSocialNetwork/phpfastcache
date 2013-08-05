<?php


/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */


class phpfastcache_example extends phpfastcache_method {

    function checkMethod() {
        // return true;
        return false;
    }



    function __construct($option = array()) {
        $this->setOption($option);
        if(!$this->checkMethod()) {
            return false;
        }

    }

    function set($keyword, $value = "", $time = 300, $option = array() ) {
        if(isset($option['skipExisting']) && $option['skipExisting'] == true) {
            // skip method
        } else {
            // add method
        }

    }

    function get($keyword, $option = array()) {
        // return null if no caching
        // return value if in caching

        return null;
    }

    function delete($keyword, $option = array()) {

    }

    function stats($option = array()) {
        $res = array(
            "info"  => "",
            "size"  =>  "",
            "data"  => "",
        );

        return $res;
    }

    function clean($option = array()) {

    }

    function isExisting($keyword) {

    }

    function increment($keyword,$step =1 , $option = array()) {

    }

    function decrement($keyword,$step =1 , $option = array()) {

    }

}