<?php


/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */


class phpfastcache_example extends phpFastCache implements phpfastcache_driver  {

    function checkdriver() {
        // return true;
        return false;
    }



    function __construct($option = array()) {
        $this->setOption($option);
        if(!$this->checkdriver() && !isset($option['skipError'])) {
            throw new Exception("Can't use this driver for your website!");
        }

    }

    function set($keyword, $value = "", $time = 300, $option = array() ) {
        if(isset($option['skipExisting']) && $option['skipExisting'] == true) {
            // skip driver
        } else {
            // add driver
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