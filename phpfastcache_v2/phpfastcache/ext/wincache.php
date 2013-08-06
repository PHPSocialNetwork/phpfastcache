<?php
/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */

class phpfastcache_wincache extends phpFastCache implements phpfastcache_driver  {

    function checkdriver() {
        if(extension_loaded('wincache') && function_exists("wincache_ucache_set"))
        {
            return true;
        }
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
            return wincache_ucache_add($keyword, $value, $time);
        } else {
            return wincache_ucache_set($keyword, $value, $time);
        }
    }

    function get($keyword, $option = array()) {
        // return null if no caching
        // return value if in caching

        $x = wincache_ucache_get($keyword,$suc);

        if($suc == false) {
            return null;
        } else {
            return $x;
        }
    }

    function delete($keyword, $option = array()) {
        return wincache_ucache_delete($keyword);
    }

    function stats($option = array()) {
        $res = array(
            "info"  =>  "",
            "size"  =>  "",
            "data"  =>  wincache_scache_info(),
        );
        return $res;
    }

    function clean($option = array()) {
        wincache_ucache_clear();
        return true;
    }

    function isExisting($keyword) {
        if(wincache_ucache_exists($keyword)) {
            return true;
        } else {
            return false;
        }
    }

    public function increment($keyword, $step = 1, $option = array()) {
        return wincache_ucache_inc($keyword, $step);
    }

    public function decrement($keyword, $step = 1,  $option = array()) {
        return wincache_ucache_dec($keyword, $step);
    }


}