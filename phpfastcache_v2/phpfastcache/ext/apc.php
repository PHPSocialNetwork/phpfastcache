<?php

/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */


class phpfastcache_apc extends phpFastCache implements phpfastcache_driver {
    function checkdriver() {
        // Check apc
        if(extension_loaded('apc') && ini_get('apc.enabled'))
        {
            return true;
        } else {
            return false;
        }
    }

    function __construct($option = array()) {
        $this->setOption($option);
        if(!$this->checkdriver() && !isset($option['skipError'])) {
            throw new Exception("Can't use this driver for your website!");
        }
    }

    function set($keyword, $value = "", $time = 300, $option = array() ) {
        if(isset($option['skipExisting']) && $option['skipExisting'] == true) {
            return apc_add($keyword,$value,$time);
        } else {
            return apc_store($keyword,$value,$time);
        }
    }

    function get($keyword, $option = array()) {
        // return null if no caching
        // return value if in caching

        $data = apc_fetch($keyword,$bo);
        if($bo === false) {
            return null;
        }
        return $data;

    }

    function delete($keyword, $option = array()) {
        return apc_delete($keyword);
    }

    function stats($option = array()) {
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

    function clean($option = array()) {
        return apc_clear_cache("user");
    }

    function isExisting($keyword) {
        if(apc_exists($keyword)) {
            return true;
        } else {
            return false;
        }
    }


    function increment($keyword,$step =1 , $option = array()) {
        $ret = apc_inc($keyword, $step, $fail);
        if($ret === false) {
            $this->set($keyword,$step,3600);
            return $step;
        } else {
            return $ret;
        }
    }

    function decrement($keyword,$step =1 , $option = array()) {
        $ret = apc_dec($keyword, $step, $fail);
        if($ret === false) {
            $this->set($keyword,$step,3600);
            return $step;
        } else {
            return $ret;
        }
    }


}