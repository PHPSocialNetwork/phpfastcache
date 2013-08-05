<?php
/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */

require_once(dirname(__FILE__)."/method.php");

// short function
if(!function_exists("__c")) {
    function __c($storage = "", $option = array()) {
        return phpfastcache($storage, $option = array());
    }
}

// main function
if(!function_exists("phpFastCache")) {
    function phpFastCache($storage = "", $option = array()) {
        if(!isset(phpFastCache::$instant[$storage])) {
            phpFastCache::$instant[$storage] = new phpFastCache($storage, $option = array());
        }
        return phpFastCache::$instant[$storage];
    }
}

// main class
class phpFastCache {
    // instant for phpfastcache();
    public static $instant = array();
    // auto, memcache, xcache, wincache, files, pdo, mpdo or whatever you see in folder "ext/";
    public static $storage = "auto"; // default for Global System

    /*
        setup everything is here
    */

    var $tmp = array();
    var $checked = array(
        "path"  => false,
    );
    var $method = NULL;
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

        if($this->isExistingClass($storage)) {
            $method = "phpfastcache_".$storage;
        } else {
            $storage = "auto";
            require_once(dirname(__FILE__)."/ext/auto.php");
            $method = "phpfastcache_auto";
        }
        $this->option("storage",$storage);

        if($this->option['securityKey'] == "auto" || $this->option['securityKey'] == "") {
            $this->option['securityKey'] = "cache.storage.".$_SERVER['HTTP_HOST'];
        }

        $this->method = new $method($this->option);

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
            $this->method->option[$name] = $this->option[$name];
            return $this;
        }
    }

    function __get($name) {
        $this->method->option = $this->option;
        return $this->method->get($name);
    }


    function __set($name, $v) {
        $this->method->option = $this->option;
        if(isset($v[1]) && is_numeric($v[1])) {
            return $this->method->set($name,$v[0],$v[1], isset($v[2]) ? $v[2] : array() );
        } else {
            die("Example ->$name = array('VALUE', 300);");
        }
    }

    function __call($name, $arg) {
        $this->method->option = $this->option;
        $t = '';
        $dem=0;
        foreach($arg as $value) {
            $t.=',$arg['.$dem.']';
            $dem++;
        }
        $t=substr($t,1);
        $string = '$res = $this->method->$name('.$t.');';
        eval($string);
        return $res;
    }

    private function isExistingClass($class) {
        if(file_exists(dirname(__FILE__)."/ext/".$class.".php")) {
            require_once(dirname(__FILE__)."/ext/".$class.".php");
            if(class_exists("phpfastcache_".$class)) {
                return true;
            }

        }

        return false;
    }

    /*
     * Required Functions
     */

    /*
     * return OS information
     *
     */
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
     * return System Information
     */
    public function systemInfo() {
        if(count($this->option("system")) == 0 ) {

            // self::startDebug("Start System Info");

            $this->option['system']['os'] = $this->getOS();
            $this->option['system']['method'] = "files";

            $this->option['system']['drivers'] = array();
            $dir = opendir(dirname(__FILE__)."/ext/");
            while($file = readdir($dir)) {
                if($file!="." && $file!=".." && strpos($file,".php") !== false) {
                    require_once(dirname(__FILE__)."/ext/".$file);
                    $namex = str_replace(".php","",$file);
                    $class = "phpfastcache_".$namex;
                    $method = new $class($this->option);
                    $method->option = $this->option;
                    if($method->checkMethod()) {
                        $this->option['system']['drivers'][$namex] = true;
                        $this->option['system']['method'] = $namex;
                    } else {
                        $this->option['system']['drivers'][$namex] = false;
                    }
                }
            }
            /*
             * PDO is highest priority with SQLite
             */
            if($this->option['system']['drivers']['sqlite'] == true) {
                $this->option['system']['method'] = "sqlite";
            }




    }

    // self::startDebug(self::$sys);
    $example = new phpfastcache_example($this->option);
    $this->option("path",$example->getPath(true));
    return $this->option;
    }

}