<?php


/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */


class phpfastcache_example extends BasePhpFastCache implements phpfastcache_driver  {

    function checkdriver() {
        // return true;
        return false;
    }

	function connectServer() {

	}

    function __construct($config = array()) {
        $this->setup($config);
        if(!$this->checkdriver() && !isset($config['skipError'])) {
            throw new Exception("Can't use this driver for your website!");
        }

    }

    function driver_set($keyword, $value = "", $time = 300, $option = array() ) {
        if(isset($option['skipExisting']) && $option['skipExisting'] == true) {
            // skip driver
        } else {
            // add driver
        }

    }

    function driver_get($keyword, $option = array()) {
        // return null if no caching
        // return value if in caching

        return null;
    }

    function driver_delete($keyword, $option = array()) {

    }

    function driver_stats($option = array()) {
        $res = array(
            "info"  => "",
            "size"  =>  "",
            "data"  => "",
        );

        return $res;
    }

    function driver_clean($option = array()) {

    }

    function driver_isExisting($keyword) {

    }



}