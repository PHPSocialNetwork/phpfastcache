<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */


require_once(dirname(__FILE__)."/driver.php");

// short function
if(!function_exists("__c")) {
	function __c($storage = "", $option = array()) {
		return phpFastCache($storage, $option);
	}
}

// main function
if(!function_exists("phpFastCache")) {
	function phpFastCache($storage = "auto", $option = array()) {
		if(!isset(phpFastCache_instances::$instances[$storage])) {
			phpFastCache_instances::$instances[$storage] = new phpFastCache($storage, $option);
		}
		return phpFastCache_instances::$instances[$storage];
	}
}

class phpFastCache_instances {
	public static $instances = array();
}

// main class
class phpFastCache {

	public static $storage = "auto";
	public static $config = array(
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

		"redis"         =>  array(
			"host"  => "127.0.0.1",
			"port"  =>  "",
			"password"  =>  "",
			"database"  =>  "",
			"timeout"   =>  ""
		),

		"extensions"    =>  array(),

	);

	var $tmp = array();
	var  $checked = array(
		"path"  => false,
		"fallback"  => false,
		"hook"      => false,
	);
	var $is_driver = false;
	var $driver = NULL;

	// default options, this will be merge to Driver's Options
	var $option = array(
		"path"  =>  "", // path for cache folder
		"htaccess"  => null, // auto create htaccess
		"securityKey"   => null,  // Key Folder, Setup Per Domain will good.
		"system"        =>  array(),
		"storage"       =>  "",
		"cachePath"     =>  "",
	);

	public static $disabled = false;

	var $chmod_permission = array(
		"default_module"    =>  "0666",
		"default_cgi"       =>  "0644",
		"chmod"             =>  "", // set this one to your chmod | blank will use default chmod above
	);

	public static $default_chmod = "";

	var $fallback = false;
	var $instant;

	function run($command, $params) {
		if($this->is_driver == true) {
			return call_user_func(array($this->instant,$command), $params);
		} else {
			return call_user_func(array($this->driver->instant,$command), $params);
		}
	}



	/*
	 * Basic Method
	 */

	function set($keyword, $value = "", $time = 0, $option = array() ) {
		/*
		 * Infinity Time
		 * Khoa. B
		 */
		if((Int)$time <= 0) {
			// 5 years, however memcached or memory cached will gone when u restart it
			// just recommended for sqlite. files
			$time = 3600*24*365*5;
		}

		/*
		 * Temporary disabled phpFastCache::$disabled = true
		 * Khoa. B
		 */

		if(self::$disabled === true) {
			return false;
		}


		$object = array(
			"value" => $value,
			"write_time"  => @date("U"),
			"expired_in"  => $time,
			"expired_time"  => @date("U") + (Int)$time,
		);

		if($this->is_driver == true) {
			return $this->driver_set($keyword,$object,$time,$option);
		} else {
			return $this->driver->driver_set($keyword,$object,$time,$option);
		}

	}

	function get($keyword, $option = array('check_expiry' => true)) {
		/*
	   * Temporary disabled phpFastCache::$disabled = true
	   * Khoa. B
	   */

		if(self::$disabled === true) {
			return null;
		}

		if($this->is_driver === true) {
			$object = $this->driver_get($keyword,$option);
		} else {
			$object = $this->driver->driver_get($keyword,$option);
		}

		if($object == null) {
			return null;
		}
		return isset($option['all_keys']) && $option['all_keys'] ? $object : $object['value'];
	}

    function check($keyword, $option = array()) {
        if($this->is_driver === true) {
            $object = $this->driver_check($keyword,$option);
        } else {
            $object = $this->driver->driver_check($keyword,$option);
        }
        if($object == null) {
            return null;
        }
        return isset($option['all_keys']) && $option['all_keys'] ? $object : $object['value'];
    }

	function _get($key){
		// this method gets values by array-style keys: arrayName[key1][key2]
		$result = null;
		$array = Array();
		$array_name = null;

		// Get array name
		$array_name_mask = "#^[a-zA-Z\d\_$]*#";
		preg_match($array_name_mask, $key, $array_name);
		$array_name = $array_name[0];

		if (!empty($array_name)){
			// get value
			$keys = Array();
			$array = $this->get($array_name);

			$pattern = '#\[[a-zA-Z\'\"\/\_\d]*\]#';
			preg_match_all($pattern, $key, $keys);
			$keys = $keys[0];

			if (!empty($keys) && !empty($array)) {
				foreach ($keys as $k => $v) {
					$keys[$k] = substr($v, 1, -1);
				}
				foreach ($keys as $subkey){
					if (array_key_exists($subkey, $array)) {
						$result = $array[$subkey];
						if (is_array($result)) $array = $result;
					} else $result = null;
				}
			} else {
				$result = $array;
			}
		}

		return $result;
	}

	function _set($key, $value, $time = 600){
		// sets values by array-style keys: arrayName[key1][key2]
		$result = Array();
		$array_name = null;

		$key_list = Array();

		// get array name
		$array_name_pattern = "#^[a-zA-Z\d\_$]*#";
		preg_match($array_name_pattern, $key, $array_name);
		$array_name = $array_name[0];

		$key_pattern = '#\[[a-zA-Z\'\"\/\_\d]*\]#';
		preg_match_all($key_pattern, $key, $keys);
		$keys = $keys[0];

		if (!empty($keys)) {
			foreach ($keys as $k => $v) {
				// load key queue
				$key_list[] = substr($v, 1, -1);
			}

			$orig_array = $this->get($array_name);
			$result = $this->replace($key_list, $orig_array, $value);
			// update cache
			$this->set($array_name, $result, $time);
		}
	}

	function replace($key_list, &$array, $value){
		if (!empty($key_list)) {
			$new_key = array_shift($key_list);
			$new_value = &$array[$new_key];

			if (!empty($key_list)) {
				// need deeper
				$this->replace($key_list, $new_value, $value);
			} else {
				// found target
				if ($value){
					// update
					$new_value = $value;
				} else {
					// delete
					unset($array[$new_key]);
				}
			}
		}

		return $array;
	}

	function _delete($key){
		// removes values by array-style keys: arrayName[key1][key2]
		$this->_set($key, null);
	}

	function getInfo($keyword, $option = array()) {
		if($this->is_driver == true) {
			$object = $this->driver_get($keyword,$option);
		} else {
			$object = $this->driver->driver_get($keyword,$option);
		}

		if($object == null) {
			return null;
		}
		return $object;
	}

	function delete($keyword, $option = array()) {
		if($this->is_driver == true) {
			return $this->driver_delete($keyword,$option);
		} else {
			return $this->driver->driver_delete($keyword,$option);
		}

	}

	function stats($option = array()) {
		if($this->is_driver == true) {
			return $this->driver_stats($option);
		} else {
			return $this->driver->driver_stats($option);
		}

	}

	function clean($option = array()) {
		if($this->is_driver == true) {
			return $this->driver_clean($option);
		} else {
			return $this->driver->driver_clean($option);
		}

	}

	function isExisting($keyword) {
		if($this->is_driver == true) {
			if(method_exists($this,"driver_isExisting")) {
				return $this->driver_isExisting($keyword);
			}
		} else {
			if(method_exists($this->driver,"driver_isExisting")) {
				return $this->driver->driver_isExisting($keyword);
			}
		}

		$data = $this->get($keyword);
		if($data == null) {
			return false;
		} else {
			return true;
		}

	}

	// Searches though the cache for keys that match the given query.
	// `$query` is a glob-like, which supports these two special characters:
	// - "*" - match 0 or more characters.
	// - "?" - match one character.
	// The function returns an array with the matched key/value pairs.
	function search($query) {
		if($this->is_driver == true) {
			if(method_exists($this,"driver_search")) {
				return $this->driver_search($query);
			}
		} else {
			if(method_exists($this->driver,"driver_isExisting")) {
				return $this->driver->driver_search($query);
			}
		}
		throw new Exception('Search method is not supported by this driver.');

	}

	function increment($keyword, $step = 1 , $option = array()) {
		$object = $this->get($keyword, array('all_keys' => true));
		if($object == null) {
			return false;
		} else {
			$value = (Int)$object['value'] + (Int)$step;
			$time = $object['expired_time'] - @date("U");
			$this->set($keyword,$value, $time, $option);
			return true;
		}
	}

	function decrement($keyword, $step = 1 , $option = array()) {
		$object = $this->get($keyword, array('all_keys' => true));
		if($object == null) {
			return false;
		} else {
			$value = (Int)$object['value'] - (Int)$step;
			$time = $object['expired_time'] - @date("U");
			$this->set($keyword,$value, $time, $option);
			return true;
		}
	}
	/*
	 * Extend more time
	 */
	function touch($keyword, $time = 300, $option = array()) {
		$object = $this->get($keyword, array('all_keys' => true));
		if($object == null) {
			return false;
		} else {
			$value = $object['value'];
			$time = $object['expired_time'] - @date("U") + $time;
			$this->set($keyword, $value,$time, $option);
			return true;
		}
	}


	/*
	* Other Functions Built-int for phpFastCache since 1.3
	*/

	public function setMulti($list = array()) {
		foreach($list as $array) {
			$this->set($array[0], isset($array[1]) ? $array[1] : 0, isset($array[2]) ? $array[2] : array());
		}
	}

	public function getMulti($list = array()) {
		$res = array();
		foreach($list as $array) {
			$name = $array[0];
			$res[$name] = $this->get($name, isset($array[1]) ? $array[1] : array());
		}
		return $res;
	}

	public function getInfoMulti($list = array()) {
		$res = array();
		foreach($list as $array) {
			$name = $array[0];
			$res[$name] = $this->getInfo($name, isset($array[1]) ? $array[1] : array());
		}
		return $res;
	}

	public function deleteMulti($list = array()) {
		foreach($list as $array) {
			$this->delete($array[0], isset($array[1]) ? $array[1] : array());
		}
	}

	public function isExistingMulti($list = array()) {
		$res = array();
		foreach($list as $array) {
			$name = $array[0];
			$res[$name] = $this->isExisting($name);
		}
		return $res;
	}

	public function incrementMulti($list = array()) {
		$res = array();
		foreach($list as $array) {
			$name = $array[0];
			$res[$name] = $this->increment($name, $array[1], isset($array[2]) ? $array[2] : array());
		}
		return $res;
	}

	public function decrementMulti($list = array()) {
		$res = array();
		foreach($list as $array) {
			$name = $array[0];
			$res[$name] = $this->decrement($name, $array[1], isset($array[2]) ? $array[2] : array());
		}
		return $res;
	}

	public function touchMulti($list = array()) {
		$res = array();
		foreach($list as $array) {
			$name = $array[0];
			$res[$name] = $this->touch($name, $array[1], isset($array[2]) ? $array[2] : array());
		}
		return $res;
	}


	/*
	 * Begin Parent Classes;
	 */




	public static function setup($name,$value = "") {
		if(!is_array($name)) {
			if($name == "storage") {
				self::$storage = $value;
			}

			self::$config[$name] = $value;
		} else {
			foreach($name as $n=>$value) {
				self::setup($n,$value);
			}
		}

	}

	public function __setChmodAuto() {
		if(phpFastCache::$default_chmod != "") {
			return phpFastCache::$default_chmod;
		}
		else if($this->chmod_permission['chmod'] == "") {
			if($this->isPHPModule()) {
				$this->chmod_permission['chmod'] = $this->chmod_permission['default_module'];
			} else {
				$this->chmod_permission['chmod'] = $this->chmod_permission['default_cgi'];
			}
		}

		return $this->chmod_permission['chmod'];
	}

	function __construct($storage = "", $option = array()) {

		if($storage == "") {
			$storage = self::$storage;
			self::option("storage", $storage);

		} else {
			self::$storage = $storage;
		}

		$this->tmp['storage'] = $storage;


		$this->option = array_merge($this->option, self::$config, $option);

		if($storage!="auto" && $storage!="" && $this->isExistingDriver($storage)) {
			$driver = "phpfastcache_".$storage;
		} else {
			$storage = $this->autoDriver();
			self::$storage = $storage;
			$driver = "phpfastcache_".$storage;
		}

		require_once(dirname(__FILE__)."/drivers/".$storage.".php");

		$this->option("storage",$storage);

		if($this->option['securityKey'] == "auto" || $this->option['securityKey'] == "") {
			$suffix = isset($_SERVER['HTTP_HOST']) ? str_replace("www.","",strtolower($_SERVER['HTTP_HOST'])) : get_current_user();
			$this->option['securityKey'] = "cache.storage.".$suffix;
		}


		$this->driver = new $driver($this->option);

		$this->fallback = !$this->driver->checkdriver();

		// do fallback
		if(method_exists($this->driver,"connectServer")) {
			$this->driver->connectServer();
		}

		if($this->driver->fallback === true) {
		//	echo 'Fall Back';
			require_once(dirname(__FILE__)."/drivers/".$this->option['fallback'].".php");
			$driver = "phpfastcache_".$this->option['fallback'];
			$this->option("storage",$this->option['fallback']);
			$this->driver = new $driver($this->option);
			$this->driver->is_driver = true;
			$this->fallback = true;
		} else {
			$this->driver->is_driver = true;
			$this->fallback = false;
		}


	}



	/*
	 * For Auto Driver
	 *
	 */

	function autoDriver() {

		$driver = "files";
		if(is_writeable($this->getPath())) {
			$driver = "files";
		}else if(extension_loaded('pdo_sqlite') && is_writeable($this->getPath())) {
			$driver = "sqlite";
		}else if(extension_loaded('apc') && ini_get('apc.enabled') && strpos(PHP_SAPI,"CGI") === false)
		{
			$driver = "apc";
		}else if(class_exists("memcached")) {
			$driver = "memcached";
		}elseif(extension_loaded('wincache') && function_exists("wincache_ucache_set")) {
			$driver = "wincache";
		}elseif(extension_loaded('xcache') && function_exists("xcache_get")) {
			$driver = "xcache";
		}else if(function_exists("memcache_connect")) {
			$driver = "memcache";
		}else if(class_exists("Redis")) {
			$driver = "redis";
		}else {
			$path = dirname(__FILE__)."/drivers";
			$dir = opendir($path);
			while($file = readdir($dir)) {
				if($file!="." && $file!=".." && strpos($file,".php") !== false) {
					require_once($path."/".$file);
					$namex = str_replace(".php","",$file);
					$class = "phpfastcache_".$namex;
					$option = $this->option;
					$option['skipError'] = true;
					$driver = new $class($option);
					$driver->option = $option;
					if($driver->checkdriver()) {
						$driver = $namex;
					}
				}
			}
		}


		return $driver;
	}

	function option($name, $value = null) {
		if($value == null) {
			if(isset($this->option[$name])) {
				return $this->option[$name];
			} else {
				return null;
			}
		} else {

			if($name == "path") {
				$this->checked['path'] = false;
				$this->driver->checked['path'] = false;
			}

			self::$config[$name] = $value;
			$this->option[$name] = $value;
			$this->driver->option[$name] = $this->option[$name];

			return $this;
		}
	}

	public function setOption($option = array()) {
		$this->option = array_merge($this->option, self::$config, $option);
		$this->checked['path'] = false;
	}



	function __get($name) {
		$this->driver->option = $this->option;
		return $this->driver->get($name);
	}


	function __set($name, $v) {
		$this->driver->option = $this->option;
		if(isset($v[1]) && is_numeric($v[1])) {
			return $this->driver->set($name,$v[0],$v[1], isset($v[2]) ? $v[2] : array() );
		} else {
			throw new Exception("Example ->$name = array('VALUE', 300);",98);
		}
	}



	/*
	 * Only require_once for the class u use.
	 * Not use autoload default of PHP and don't need to load all classes as default
	 */
	private function isExistingDriver($class) {
		if(file_exists(dirname(__FILE__)."/drivers/".$class.".php")) {
			require_once(dirname(__FILE__)."/drivers/".$class.".php");
			if(class_exists("phpfastcache_".$class)) {
				return true;
			}
		}

		return false;
	}


	/*
	 * return System Information
	 */
	public function systemInfo() {
		$backup_option = $this->option;
		if(count($this->option("system")) == 0 ) {
			$this->option['system']['driver'] = "files";
			$this->option['system']['drivers'] = array();
			$dir = @opendir(dirname(__FILE__)."/drivers/");
			if(!$dir) {
				throw new Exception("Can't open file dir ext",100);
			}

			while($file = @readdir($dir)) {
				if($file!="." && $file!=".." && strpos($file,".php") !== false) {
					require_once(dirname(__FILE__)."/drivers/".$file);
					$namex = str_replace(".php","",$file);
					$class = "phpfastcache_".$namex;
					$this->option['skipError'] = true;
					$driver = new $class($this->option);
					$driver->option = $this->option;
					if($driver->checkdriver()) {
						$this->option['system']['drivers'][$namex] = true;
						$this->option['system']['driver'] = $namex;
					} else {
						$this->option['system']['drivers'][$namex] = false;
					}
				}
			}


			/*
			 * PDO is highest priority with SQLite
			 */
			if($this->option['system']['drivers']['sqlite'] == true) {
				$this->option['system']['driver'] = "sqlite";
			}




		}

		$example = new phpfastcache_example($this->option);
		$this->option("path",$example->getPath(true));
		$this->option = $backup_option;
		return $this->option;
	}





	public function getOS() {
		$os = array(
			"os" => PHP_OS,
			"php" => PHP_SAPI,
			"system"    => php_uname(),
			"unique"    => md5(php_uname().PHP_OS.PHP_SAPI)
		);
		return $os;
	}


	/*
	 * Object for Files & SQLite
	 */
	public function encode($data) {
		return serialize($data);
	}

	public function decode($value) {
		$x = @unserialize($value);
		if($x == false) {
			return $value;
		} else {
			return $x;
		}
	}



	/*
	 * Auto Create .htaccess to protect cache folder
	 */

	public function htaccessGen($path = "") {
		if($this->option("htaccess") == true) {

			if(!file_exists($path."/.htaccess")) {
				//   echo "write me";
				$html = "order deny, allow \r\n
deny from all \r\n
allow from 127.0.0.1";

				$f = @fopen($path."/.htaccess","w+");
				if(!$f) {
					throw new Exception("Can't create .htaccess",97);
				}
				fwrite($f,$html);
				fclose($f);


			} else {
				//   echo "got me";
			}
		}

	}

	/*
	* Check phpModules or CGI
	*/

	public function isPHPModule() {
		if(PHP_SAPI == "apache2handler") {
			return true;
		} else {
			if(strpos(PHP_SAPI,"handler") !== false) {
				return true;
			}
		}
		return false;
	}


	/*
	 * return PATH for Files & PDO only
	 */
	public function getPath($create_path = false) {

		if($this->option['path'] == "" && self::$config['path']!="") {
			$this->option("path", self::$config['path']);
		}


		if ($this->option['path'] =='')
		{
			// revision 618
			if($this->isPHPModule()) {
				$tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
				$this->option("path",$tmp_dir);

			} else {
				$this->option("path", dirname(__FILE__));
			}

			if(self::$config['path'] == "") {
				self::$config['path']=  $this->option("path");
			}

		}


		$full_path = $this->option("path")."/".$this->option("securityKey")."/";

		if($create_path == false && $this->checked['path'] == false) {

			if(!file_exists($full_path) || !is_writable($full_path)) {
				if(!file_exists($full_path)) {
					@mkdir($full_path,$this->__setChmodAuto());
				}
				if(!is_writable($full_path)) {
					@chmod($full_path,$this->__setChmodAuto());
				}
				if(!file_exists($full_path) || !is_writable($full_path)) {
					throw new Exception("Sorry, Please create ".$this->option("path")."/".$this->option("securityKey")."/ and SET Mode 0777 or any Writable Permission!" , 100);
				}
			}


			$this->checked['path'] = true;
			$this->htaccessGen($full_path);
		}

		$this->option['cachePath'] = $full_path;
		return $this->option['cachePath'];
	}

	/*
	 * Read File
	 * Use file_get_contents OR ALT read
	 */

	protected function readfile($file) {
		if(function_exists("file_get_contents")) {
			return file_get_contents($file);
		} else {
			$string = "";

			$file_handle = @fopen($file, "r");
			if(!$file_handle) {
				throw new Exception("Can't Read File",96);

			}
			while (!feof($file_handle)) {
				$line = fgets($file_handle);
				$string .= $line;
			}
			fclose($file_handle);

			return $string;
		}
	}

	protected function backup() {
		return phpFastCache(self::$config['fallback']);
	}


	protected function required_extension($name) {
		require_once(dirname(__FILE__)."/extensions/".$name);
	}


}
