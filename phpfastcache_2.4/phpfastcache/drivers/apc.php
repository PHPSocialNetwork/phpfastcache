<?php

/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */


class phpfastcache_apc extends phpFastCache implements phpfastcache_driver {
    function checkdriver() {
        // Check apc
        if(extension_loaded('apc') && ini_get('apc.enabled'))
        {
            return true;
        } else {
	        $this->fallback = true;
            return false;
        }
    }

    function __construct($option = array()) {
        $this->setOption($option);
        if(!$this->checkdriver() && !isset($option['skipError'])) {
	        $this->fallback = true;
        }
    }

    function driver_set($keyword, $value = "", $time = 300, $option = array() ) {
        if(isset($option['skipExisting']) && $option['skipExisting'] == true) {
            return apc_add($keyword,$value,$time);
        } else {
            return apc_store($keyword,$value,$time);
        }
    }

    function driver_get($keyword, $option = array()) {
        // return null if no caching
        // return value if in caching

        $data = apc_fetch($keyword,$bo);
        if($bo === false) {
            return null;
        }
        return $data;

    }

    function driver_delete($keyword, $option = array()) {
        return apc_delete($keyword);
    }

    function driver_stats($option = array()) {
        $res = array(
            "info" => "",
            "size"  => "",
            "data"  =>  "",
        );

        try {
            $res['data'] = apc_cache_info("user");
        } catch(Exception $e) {
            $res['data'] =  array();
        }

        return $res;
    }

    function driver_clean($option = array()) {
        @apc_clear_cache();
        @apc_clear_cache("user");
    }

    function driver_isExisting($keyword) {
        if(apc_exists($keyword)) {
            return true;
        } else {
            return false;
        }
    }





}