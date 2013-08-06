<?php
/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */

require_once(dirname(__FILE__)."/driver.php");

// short function
if(!function_exists("__c")) {
    function __c($storage = "", $option = array()) {
        return phpfastcache($storage, $option);
    }
}

// main function
if(!function_exists("phpFastCache")) {
    function phpFastCache($storage = "", $option = array()) {
        if(!isset(phpFastCache::$instances[$storage])) {
            phpFastCache::$instances[$storage] = new phpFastCache($storage, $option);
        }
        return phpFastCache::$instances[$storage];
    }
}

// main class
class phpFastCache {
    public static $instances = array();
    public static $storage = "auto"; // default for Global System
    var $tmp = array();
    var $checked = array(
        "path"  => false,
    );
    var $driver = NULL;

    // default options, this will be merge to Driver's Options
    var $option = array(
        "path"  =>  "", // path for cache folder
        "htaccess"  => true, // auto create htaccess
        "securityKey"   => "auto",  // Key Folder, Setup Per Domain will good.
        "server"        =>  array(
            array("127.0.0.1",11211,1),
            //  array("new.host.ip",11211,1),
        ),

        "system"        =>  array(),
        "storage"       =>  "",
        "cachePath"     =>  "",
    );

    function __construct($storage = "", $option = array()) {
        if($storage == "") {
            $storage = self::$storage;
        } else {
            self::$storage = $storage;
        }
        $this->tmp['storage'] = $storage;

        $this->option = array_merge($this->option, $option);

        if($this->isExistingDriver($storage)) {
            $driver = "phpfastcache_".$storage;
        } else {
            $storage = "auto";
            require_once(dirname(__FILE__)."/ext/auto.php");
            $driver = "phpfastcache_auto";
        }

        $this->option("storage",$storage);
        if($this->option['securityKey'] == "auto" || $this->option['securityKey'] == "") {
            $this->option['securityKey'] = "cache.storage.".$_SERVER['HTTP_HOST'];
        }


        $this->driver = new $driver($this->option);

    }

    function option($name, $value = null) {
        if($value == null) {
            if(isset($this->option[$name])) {
                return $this->option[$name];
            } else {
                return null;
            }
        } else {
            $this->option[$name] = $value;
            $this->driver->option[$name] = $this->option[$name];
            return $this;
        }
    }

    public function setOption($option = array()) {
        $this->option = array_merge($this->option, $option);
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

    function __call($name, $arg) {
        $this->driver->option = $this->option;
        $res = call_user_func_array($name, $arg);
        return $res;
    }

    /*
     * Only require_once for the class u use.
     * Not use autoload default of PHP and don't need to load all classes as default
     */
    private function isExistingDriver($class) {
        if(file_exists(dirname(__FILE__)."/ext/".$class.".php")) {
            require_once(dirname(__FILE__)."/ext/".$class.".php");
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
        if(count($this->option("system")) == 0 ) {


            $this->option['system']['driver'] = "files";

            $this->option['system']['drivers'] = array();

            $dir = @opendir(dirname(__FILE__)."/ext/");
            if(!$dir) {
                throw new Exception("Can't open file dir ext",100);
            }

            while($file = @readdir($dir)) {
                if($file!="." && $file!=".." && strpos($file,".php") !== false) {
                    require_once(dirname(__FILE__)."/ext/".$file);
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
    return $this->option;
    }


    /*
      * Other Functions Built-int for phpFastCache since 1.3
      */

    public function setMulti($list = array()) {
        foreach($list as $array) {
            $this->set($array[0], isset($array[1]) ? $array[1] : 300, isset($array[2]) ? $array[2] : array());
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
    public function encode($data,$time_in_second = 600, $option = array()) {
        $object = array(
            "data"  => $data,
            "time"  => $time_in_second,
            "exp"   => (Int)@date("U") + (Int)$time_in_second,
        );
        foreach($option as $name=>$value) {
            $object['option'][$name] = $value;
        }

        return serialize($object);
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
    public function getPath($skip_create = false) {

        if ($this->option("path") =='')
        {
            // revision 618
            if($this->isPHPModule()) {
                $tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
                $this->option("path",$tmp_dir);

            } else {
                $this->option("path", dirname(__FILE__));
            }

        }

        if($skip_create == false && $this->checked['path'] == false) {
            if(!file_exists($this->option("path")."/".$this->option("securityKey")."/")) {
                if(!file_exists($this->option("path")."/".$this->option("securityKey")."/")) {
                    @mkdir($this->option("path")."/".$this->option("securityKey")."/",0777);
                }
                if(!is_writable($this->option("path")."/".$this->option("securityKey")."/")) {
                    @chmod($this->option("path")."/".$this->option("securityKey")."/",0777);
                }
                if(!file_exists($this->option("path")."/".$this->option("securityKey")."/") || !is_writable($this->option("path")."/".$this->option("securityKey")."/")) {
                    throw new Exception("Sorry, Please create ".$this->option("path")."/".$this->option("securityKey")."/ and SET Mode 0777 or any Writable Permission!" , 100);
                }

            }

            $this->checked['path'] = true;
            // Revision 618
            $this->htaccessGen($this->option("path")."/".$this->option("securityKey")."/");

        }

        $this->option['cachePath'] = $this->option("path")."/".$this->option("securityKey")."/";
        // $this->option['cachePath'] = str_replace("//","/",$this->option['cachePath']);

        return $this->option['cachePath'];
    }

    /*
     * Read File
     * Use file_get_contents OR ALT read
     */

    function readfile($file) {
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

    /*
     * Basic Method
     */

    function set($keyword, $value = "", $time = 300, $option = array() ) {
        return $this->driver->set($keyword,$value,$time,$option);
    }

    function get($keyword, $option = array()) {
        return $this->driver->get($keyword,$option);
    }

    function delete($keyword, $option = array()) {
        return $this->driver->delete($keyword,$option);
    }

    function stats($option = array()) {
        return $this->driver->stats($option);
    }

    function clean($option = array()) {
        return $this->driver->clean($option);
    }

    function isExisting($keyword) {
        return $this->driver->isExisting($keyword);
    }

    function increment($keyword,$step =1 , $option = array()) {
        return $this->driver->increment($keyword, $step, $option);
    }

    function decrement($keyword,$step =1 , $option = array()) {
        return $this->driver->decrement($keyword,$step,$option);
    }

}