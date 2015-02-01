<?php
// phpFastCache Library
require_once(dirname(__FILE__)."/base.php");

// OK, setup your cache
phpFastCache::$storage = "auto";
phpFastCache::$config = array(
	"storage"   =>  "auto",
	/*
	 * Fall back when old driver is not support
	 */
	"fallback"  => "files",

	"securityKey"   =>  "auto",
	"htaccess"      => true,
	"path"      =>  "",

	"memcache"        =>  array(
		array("127.0.0.1",11211,1),
		//  array("new.host.ip",11211,1),
	),

	"redis"         =>  array("127.0.0.1",6379),

	"extensions"    =>  array(),
);


// temporary disabled phpFastCache
phpFastCache::$disabled = false;
