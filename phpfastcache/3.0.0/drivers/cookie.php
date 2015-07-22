<?php

/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 *
 * Cookie Caching on Visitors Browser
 */


class phpfastcache_cookie extends BasePhpFastCache implements phpfastcache_driver {


	function checkdriver() {
		// Check memcache
		if(function_exists("setcookie")) {
			return true;
		}
		$this->fallback = true;
		return false;
	}

	function __construct($config = array()) {
		$this->setup($config);
		if(!$this->checkdriver() && !isset($config['skipError'])) {
			$this->fallback = true;
		}
		if(class_exists("Redis")) {
			$this->instant = new Redis();
		}

	}

	function connectServer() {
		// for cookie check output
		if(!isset($_COOKIE['phpfastcache'])) {
			if(!@setcookie("phpfastcache",1,10)) {
				$this->fallback = true;
			}
		}

	}

	function driver_set($keyword, $value = "", $time = 300, $option = array() ) {
		$this->connectServer();
		$keyword = "phpfastcache_".$keyword;
		return @setcookie($keyword, $this->encode($value), $time, "/");

	}

	function driver_get($keyword, $option = array()) {
		$this->connectServer();
		// return null if no caching
		// return value if in caching
		$keyword = "phpfastcache_".$keyword;
		$x = isset($_COOKIE[$keyword]) ? $this->decode($_COOKIE['keyword']) : false;
		if($x == false) {
			return null;
		} else {
			return $x;
		}
	}

	function driver_delete($keyword, $option = array()) {
		$this->connectServer();
		$keyword = "phpfastcache_".$keyword;
		@setcookie($keyword,null,-10);
		$_COOKIE[$keyword] = null;
	}

	function driver_stats($option = array()) {
		$this->connectServer();
		$res = array(
			"info"  => "",
			"size"  =>  "",
			"data"  => $_COOKIE
		);

		return $res;

	}

	function driver_clean($option = array()) {
		$this->connectServer();
		foreach($_COOKIE as $keyword=>$value) {
			if(strpos($keyword,"phpfastcache") !== false) {
				@setcookie($keyword,null,-10);
				$_COOKIE[$keyword] = null;
			}
		}
	}

	function driver_isExisting($keyword) {
		$this->connectServer();
		$x = $this->get($keyword);
		if($x == null) {
			return false;
		} else {
			return true;
		}
	}



}