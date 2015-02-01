<?php

/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 *
 * Redis Extension with:
 * http://pecl.php.net/package/redis
 */


class phpfastcache_redis extends phpFastCache implements phpfastcache_driver {

	var $checked_redis = false;

    function checkdriver() {
        // Check memcache
        if(class_exists("Redis")) {
            return true;
        }
	    $this->fallback = true;
        return false;
    }

    function __construct($option = array()) {
        $this->setOption($option);
        if(!$this->checkdriver() && !isset($option['skipError'])) {
	        $this->fallback = true;
        }
	    if(class_exists("Redis")) {
		    $this->instant = new Redis();
	    }

    }

    function connectServer() {

	    $server = isset($this->option['redis']) ? $this->option['redis'] : array("127.0.0.1",6379);
	    $p1 = isset($server[0]) ? $server[0] : "127.0.0.1";
	    $p2 = isset($server[1]) ? $server[1] : 6379;
	    $p3 = isset($server[2]) ? $server[2] : 0;

	    if($this->checked_redis === false) {
		    if(!$this->instant->connect($p1,$p2,$p3)) {
			    $this->checked_redis = true;
			    $this->fallback = true;
			    return false;
		    } else {
			    $this->checked_redis = true;
			    return true;
		    }
	    }

	    return true;
    }

    function driver_set($keyword, $value = "", $time = 300, $option = array() ) {
        if($this->connectServer()) {
	        $value = $this->encode($value);
	        if (isset($option['skipExisting']) && $option['skipExisting'] == true) {
		        return $this->instant->set($keyword, $value, array('xx', 'ex' => $time));
	        } else {
		        return $this->instant->set($keyword, $value, $time);
	        }
        } else {
			return $this->backup()->set($keyword, $value, $time, $option);
        }
    }

    function driver_get($keyword, $option = array()) {
        if($this->connectServer()) {
	        // return null if no caching
	        // return value if in caching'
	        $x = $this->instant->get($keyword);
	        if($x == false) {
		        return null;
	        } else {

		        return $this->decode($x);
	        }
        } else {
			$this->backup()->get($keyword, $option);
        }

    }

    function driver_delete($keyword, $option = array()) {

        if($this->connectServer()) {
	        $this->instant->delete($keyword);
        }

    }

    function driver_stats($option = array()) {
        if($this->connectServer()) {
	        $res = array(
		        "info"  => "",
		        "size"  =>  "",
		        "data"  => $this->instant->info(),
	        );

	        return $res;
        }

	    return array();

    }

    function driver_clean($option = array()) {
        if($this->connectServer()) {
	        $this->instant->flushDB();
        }

    }

    function driver_isExisting($keyword) {
        if($this->connectServer()) {
	        $x = $this->instant->exists($keyword);
	        if($x == null) {
		        return false;
	        } else {
		        return true;
	        }
        } else {
	        return $this->backup()->isExisting($keyword);
        }

    }



}