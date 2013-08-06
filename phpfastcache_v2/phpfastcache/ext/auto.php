<?php
/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */


class phpfastcache_auto extends phpFastCache implements phpfastcache_driver  {
    var $auto = "";
    var $instant = "";

    function __construct($option = array()) {
        $this->setOption($option);

        $this->option['cachePath'] = $this->getPath();

        $driver = $this->autodriver();
        require_once(dirname(__FILE__)."/".$driver.".php");
        $driver = "phpfastcache_".$driver;
        $this->instant = new $driver();

    }

    function checkdriver() {
        return true;
    }

    /*
     * Pick A Good One
     */
    function autodriver() {

        $driver = "files";
        if($this->auto == "") {
            if(extension_loaded('apc') && ini_get('apc.enabled') && strpos(PHP_SAPI,"CGI") === false)
            {
                    $driver = "apc";
            }elseif(extension_loaded('xcache'))
            {
                    $driver = "xcache";
            }elseif(extension_loaded('pdo_sqlite') && is_writeable($this->option['cachePath'])) {
                    $driver = "sqlite";
            }elseif(is_writeable($this->option['cachePath'])) {
                    $driver = "files";
            }else if(class_exists("memcached")) {
                    $driver = "memcached";
            }elseif(extension_loaded('wincache') && function_exists("wincache_ucache_set")) {
                    $driver = "wincache";
            }elseif(extension_loaded('xcache') && function_exists("xcache_get")) {
                    $driver = "xcache";
            }else if(function_exists("memcache_connect")) {
                    $driver = "memcache";
            }else {
                while($file = readdir(__FILE__)) {
                    if($file!="." && $file!=".." && strpos($file,".php") !== false) {
                        require_once(dirname(__FILE__)."/".$file);
                        $namex = str_replace(".php","",$file);
                        $class = "phpfastcache_".$namex;
                        $driver = new $class();
                        $driver->option = $this->option;
                        if($driver->checkdriver()) {
                            $driver = $namex;
                        }
                    }
                }
            }
        }

        $this->auto = $driver;
        return $driver;
    }

    function set($keyword, $value = "", $time = 300, $option = array() ) {
        return $this->instant->set($keyword,$value,$time,$option);
    }

    function get($keyword, $option = array()) {

        return $this->instant->get($keyword,$option);
    }

    function delete($keyword, $option = array()) {
        return $this->instant->delete($keyword,$option);
    }

    function stats($option = array()) {
        return $this->instant->stats($option);
    }

    function clean($option = array()) {
        return $this->instant->clean($option);
    }

    function isExisting($keyword) {
        return $this->instant->isExisting($keyword);
    }

    function increment($keyword,$step =1 , $option = array()) {
        return $this->instant->increment($keyword, $step, $option);
    }

    function decrement($keyword,$step =1 , $option = array()) {
        return $this->instant->decrement($keyword,$step,$option);
    }
}