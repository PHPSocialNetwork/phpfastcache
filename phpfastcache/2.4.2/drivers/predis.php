<?php

/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 *
 * Redis Extension with:
 * http://pecl.php.net/package/redis
 */


class phpfastcache_predis extends phpFastCache implements phpfastcache_driver {

	var $checked_redis = false;

    function checkdriver() {
        // Check memcache
	    $this->required_extension("predis-1.0/autoload.php");
	    try {
		    Predis\Autoloader::register();
	    } catch(Exception $e) {

	    }
	    return true;
    }

    function __construct($option = array()) {
        $this->setOption($option);
	    $this->required_extension("predis-1.0/autoload.php");



    }

    function connectServer() {

	    $server = isset($this->option['redis']) ? $this->option['redis'] : array("127.0.0.1",6379);
	    $p1 = isset($server[0]) ? $server[0] : "127.0.0.1";
	    $p2 = isset($server[1]) ? $server[1] : 6379;
	    $p3 = isset($server[2]) ? $server[2] : 0;

	    if($this->checked_redis === false) {

		    $this->instant = new Predis\Client('tcp://'.$p1.':'.$p2);

		    $this->checked_redis = true;

		    if(!$this->instant) {
			    $this->fallback = true;
			    return false;
		    } else {
			    return true;
		    }
	    }

	    return true;
    }

    function driver_set($keyword, $value = "", $time = 300, $option = array() ) {
        if($this->connectServer()) {
	        $value = $this->encode($value);
	        if (isset($option['skipExisting']) && $option['skipExisting'] == true) {
		        return $this->instant->setex($keyword, $time, $value);
	        } else {
		        return $this->instant->setex($keyword, $time, $value );
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