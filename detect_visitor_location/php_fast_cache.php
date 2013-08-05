<?php
/* Revision 620
 * ALl EXAMPLE & DOCUMENT ARE ON www.phpFastCache.com
 * IF YOU FOUND A BUG, PLEASE GO THERE: http://www.codehelper.io <-- Post your issues and I will fix it.
 * Open new issue and I will fix it for you in 24 hours
 * I stopped support issues on GitHub
 */

class phpFastCache {
    // Public OPTIONS
    // Can be set by phpFastCache::$option_name = $value|array|string
    public static $storage = "auto"; // PDO | mpdo | Auto | Files | memcache | apc | wincache | xcache
    public static $files_cleanup_after = 1; // hour | auto clean up files after this
    public static $autosize = 40; // Megabytes
    public static $path = ""; // PATH/TO/CACHE/ default will be current path
    public static $securityKey = "cache.storage"; // phpFastCache::$securityKey = "newKey";
    public static $securityHtAccess = true; // auto create .htaccess
    public static $option = array();
    public static $server = array(array("localhost",11211)); // for MemCache
    public static $useTmpCache = false; // use for get from Tmp Memory, will be faster in checking cache on LOOP.
    public static $debugging = false; // turn true for debugging


    // NOTHING TO CHANGE FROM HERE
    private static $step_debugging = 0;
    private static $Tmp = array();
    private static $supported_api = array("pdo","mpdo","files","memcache","memcached","apc","xcache","wincache");
    private static $filename = "pdo.caching";
    private static $table = "objects";
    private static $autodb = "";
    private static $multiPDO = array();


    public static $sys = array();
    private static $checked = array(
        "path"  =>  false,
        "servers"   =>  array(),
        "config_file"   => "",
    );
    private static $objects = array(
        "memcache"  =>  "",
        "memcached" =>  "",
        "pdo"       =>  "",
    );


    private static function getOS() {
        $os = array(
            "os" => PHP_OS,
            "php" => PHP_SAPI,
            "system"    => php_uname(),
            "unique"    => md5(php_uname().PHP_OS.PHP_SAPI)
        );
        return $os;
    }



    public static function systemInfo() {
        // self::startDebug(self::$sys,"Check Sys",__LINE__,__FUNCTION__);

        if(count(self::$sys) == 0 ) {

            // self::startDebug("Start System Info");

            self::$sys['os'] = self::getOS();

            self::$sys['errors'] = array();
            self::$sys['storage'] = "";
            self::$sys['method'] = "pdo";
            self::$sys['drivers'] = array(
                "apc"   =>  false,
                "xcache"    => false,
                "memcache"  => false,
                "memcached"  => false,
                "wincache"  => false,
                "pdo"       => false,
                "mpdo"     => false,
                "files"     => false,

            );



            // Check apc
            if(extension_loaded('apc') && ini_get('apc.enabled'))
            {
                self::$sys['drivers']['apc']   = true;
                self::$sys['storage'] = "memory";
                self::$sys['method'] = "apc";
            }

            // Check xcache
            if(extension_loaded('xcache') && function_exists("xcache_get"))
            {
                self::$sys['drivers']['xcache']   = true;
                self::$sys['storage'] = "memory";
                self::$sys['method'] = "xcache";
            }

            if(extension_loaded('wincache') && function_exists("wincache_ucache_set"))
            {
                self::$sys['drivers']['wincache']   = true;
                self::$sys['storage'] = "memory";
                self::$sys['method'] = "wincache";
            }

            // Check memcache
            if(function_exists("memcache_connect")) {
                self::$sys['drivers']['memcache'] = true;

                try {
                    memcache_connect("127.0.0.1");
                    self::$sys['storage'] = "memory";
                    self::$sys['method'] = "memcache";
                } catch (Exception $e) {

                }
            }


            // Check memcached
            if(class_exists("memcached")) {
                self::$sys['drivers']['memcached'] = true;

                try {
                    $memcached = new memcached();
                    $memcached->addServer("127.0.0.1","11211");
                    self::$sys['storage'] = "memory";
                    self::$sys['method'] = "memcached";

                } catch (Exception $e) {

                }
            }

            if(extension_loaded('pdo_sqlite')) {
                self::$sys['drivers']['pdo']   = true;
                self::$sys['drivers']['mpdo']   = true;
            }

            if(is_writable(self::getPath(true))) {
                self::$sys['drivers']['files'] = true;
            }

            if(self::$sys['storage'] == "") {

                if(extension_loaded('pdo_sqlite')) {
                    self::$sys['storage'] = "disk";
                    self::$sys['method'] = "pdo";

                } else {

                    self::$sys['storage'] = "disk";
                    self::$sys['method'] = "files";

                }

            }



            if(self::$sys['storage'] == "disk" && !is_writable(self::getPath())) {
                self::$sys['errors'][] = "Please Create & CHMOD 0777 or any Writeable Mode for ".self::getPath();
            }




        }

        // self::startDebug(self::$sys);
        return self::$sys;
    }

    // return Folder Cache PATH
    // PATH Edit by SecurityKey
    // Auto create, Chmod and Warning

    // Revision 618
    // PHP_SAPI =  apache2handler should go to tmp
    private static function isPHPModule() {
        if(PHP_SAPI == "apache2handler") {
            return true;
        } else {
            if(strpos(PHP_SAPI,"handler") !== false) {
                return true;
            }
        }
        return false;
    }
    // Revision 618
    // Security with .htaccess
    static function htaccessGen($path = "") {
        if(self::$securityHtAccess == true) {

            if(!file_exists($path."/.htaccess")) {
             //   echo "write me";
                $html = "order deny, allow \r\n
deny from all \r\n
allow from 127.0.0.1";
                $f = @fopen($path."/.htaccess","w+");
                @fwrite($f,$html);
                @fclose($f);
            } else {
             //   echo "got me";
            }
        }

    }

    private static function getPath($skip_create = false) {

        if (self::$path=='')
        {
            // revision 618
            if(self::isPHPModule()) {
                $tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
                self::$path = $tmp_dir;
            } else {
                self::$path = dirname(__FILE__);
            }

        }

        if($skip_create == false && self::$checked['path'] == false) {
            if(!file_exists(self::$path."/".self::$securityKey."/") || !is_writable(self::$path."/".self::$securityKey."/")) {
                if(!file_exists(self::$path."/".self::$securityKey."/")) {
                    @mkdir(self::$path."/".self::$securityKey."/",0777);
                }
                if(!is_writable(self::$path."/".self::$securityKey."/")) {
                    @chmod(self::$path."/".self::$securityKey."/",0777);
                }
                if(!file_exists(self::$path."/".self::$securityKey."/") || !is_writable(self::$path."/".self::$securityKey."/")) {
                    die("Sorry, Please create ".self::$path."/".self::$securityKey."/ and SET Mode 0777 or any Writable Permission!" );
                }

            }

            self::$checked['path'] = true;
            // Revision 618
            self::htaccessGen(self::$path."/".self::$securityKey."/");

        }



        return self::$path."/".self::$securityKey."/";


    }

    // return method automatic;
    // APC will be TOP, then Memcached, Memcache, PDO and Files
    public static function autoconfig($name = "") {
        // self::startDebug($name,"Check Name",__LINE__,__FUNCTION__);

        $cache = self::cacheMethod($name);
        if($cache != "" && $cache != self::$storage && $cache!="auto") {
            return $cache;
        }

        // self::startDebug($cache,"Check Cache",__LINE__,__FUNCTION__);

        $os = self::getOS();
        // self::startDebug(self::$storage,"User Set",__LINE__,__FUNCTION__);
        if(self::$storage == "" || self::$storage == "auto") {
            // self::startDebug(self::$storage,"User Set Auto",__LINE__,__FUNCTION__);

            if(extension_loaded('apc') && ini_get('apc.enabled') && strpos(PHP_SAPI,"CGI") === false)
            {

                self::$sys['drivers']['apc']   = true;
                self::$sys['storage'] = "memory";
                self::$sys['method'] = "apc";

                // self::startDebug(self::$sys,"GOT APC",__LINE__,__FUNCTION__);

            }elseif(extension_loaded('xcache'))
            {
                self::$sys['drivers']['xcache']   = true;
                self::$sys['storage'] = "memory";
                self::$sys['method'] = "xcache";
                // self::startDebug(self::$sys,"GOT XCACHE",__LINE__,__FUNCTION__);

            } else {
                // fix PATH for existing
                $reconfig = false;
                // self::startDebug(self::getPath()."/config.".$os['unique'].".cache.ini","CHECK CONFIG FILE",__LINE__,__FUNCTION__);


                if (file_exists(self::getPath()."/config.".$os['unique'].".cache.ini"))
                {
                    $info = self::decode(file_get_contents(self::getPath()."/config.".$os['unique'].".cache.ini"));

                    // self::startDebug($info,"CHECK INFO",__LINE__,__FUNCTION__);

                    if(!isset($info['value'])) {
                        $reconfig = true;

                    } else {
                        $info = $info['value'];
                        self::$sys = $info;

                    }


                } else {

                    $info = self::systemInfo();
                    // self::startDebug($info,"CHECK INFO BY SYSTEM INFO",__LINE__,__FUNCTION__);
                }

                if(isset($info['os']['unique'])) {

                    if($info['os']['unique'] != $os['unique']) {
                        $reconfig = true;
                    }
                } else {
                    $reconfig = true;
                }

                if(!file_exists(self::getPath()."/config.".$os['unique'].".cache.ini") || $reconfig == true) {

                    $info = self::systemInfo();
                    self::$sys = $info;
                    // self::startDebug($info,"Check Info",__LINE__,__FUNCTION__);

                    try {
                        $f = fopen(self::getPath()."/config.".$os['unique'].".cache.ini","w+");
                        fwrite($f,self::encode($info));
                        fclose($f);

                    } catch (Exception $e) {
                        die("Please chmod 0777 ".self::getPath()."/config.".$os['unique'].".cache.ini");
                    }
                } else {

                }

            }



            self::$storage = self::$sys['method'];


        } else {

            if(in_array(self::$storage,array("files","pdo","mpdo"))) {
                self::$sys['storage'] = "disk";
            }elseif(in_array(self::$storage,array("apc","memcache","memcached","wincache","xcache"))) {
                self::$sys['storage'] = "memory";
            } else {
                self::$sys['storage'] = "";
            }

            if(self::$sys['storage'] == "" || !in_array(self::$storage,self::$supported_api)) {
                die("Don't have this Cache ".self::$storage." In your System! Please double check!");
            }

            self::$sys['method'] = strtolower(self::$storage);

        }

        if(self::$sys['method'] == "files") {
            $last_cleanup = self::files_get("last_cleanup_cache");
            if($last_cleanup == null) {
                self::files_cleanup();
                self::files_set("last_cleanup_cache",@date("U"),3600*self::$files_cleanup_after);
            }
        }

        // self::startDebug(self::$sys,"Check RETURN SYS",__LINE__,__FUNCTION__);

        return self::$sys['method'];

    }



    private static function cacheMethod($name = "") {
        $cache = self::$storage;
        if(is_array($name)) {
            $key = array_keys($name);
            $key = $key[0];
            if(in_array($key,self::$supported_api)) {
                $cache = $key;
            }
        }
        return $cache;
    }


    public static function safename($name) {
        return strtolower(preg_replace("/[^a-zA-Z0-9_\s\.]+/","",$name));
    }





    private static function encode($value,$time_in_second = "") {
        $value = serialize(array(
            "time"  => @date("U"),
            "value" => $value,
            "endin" => $time_in_second
        ));
        return $value;
    }

    private static function decode($value) {
        $x = @unserialize($value);
        if($x == false) {
            return $value;
        } else {
            return $x;
        }
    }

    /*
     * Start Public Static
     */

    public static function cleanup($option = "") {
        $api = self::autoconfig();
        self::$Tmp = array();

        switch ($api) {
            case "pdo":
                return self::pdo_cleanup($option);
                break;
            case "mpdo":
                return self::pdo_cleanup($option);
                break;
            case "files":
                return self::files_cleanup($option);
                break;
            case "memcache":
                return self::memcache_cleanup($option);
                break;
            case "memcached":
                return self::memcached_cleanup($option);
                break;
            case "wincache":
                return self::wincache_cleanup($option);
                break;
            case "apc":
                return self::apc_cleanup($option);
                break;
            case "xcache":
                return self::xcache_cleanup($option);
                break;
            default:
                return self::pdo_cleanup($option);
                break;
        }

    }

    public static function delete($name = "string|array(db->item)") {

        $api = self::autoconfig($name);
        if(self::$useTmpCache == true) {
            $tmp_name = md5(serialize($api.$name));
            if(isset(self::$Tmp[$tmp_name])) {
                unset(self::$Tmp[$tmp_name]);
            }
        }

        switch ($api) {
            case "pdo":
                return self::pdo_delete($name);
                break;
            case "mpdo":
                return self::pdo_delete($name);
                break;
            case "files":
                return self::files_delete($name);
                break;
            case "memcache":
                return self::memcache_delete($name);
                break;
            case "memcached":
                return self::memcached_delete($name);
                break;
            case "wincache":
                return self::wincache_delete($name);
                break;
            case "apc":
                return self::apc_delete($name);
                break;
            case "xcache":
                return self::xcache_delete($name);
                break;
            default:
                return self::pdo_delete($name);
                break;
        }

    }


    public static function exists($name = "string|array(db->item)") {

        $api = self::autoconfig($name);
        switch ($api) {
            case "pdo":
                return self::pdo_exist($name);
                break;
            case "mpdo":
                return self::pdo_exist($name);
                break;
            case "files":
                return self::files_exist($name);
                break;
            case "memcache":
                return self::memcache_exist($name);
                break;
            case "memcached":
                return self::memcached_exist($name);
                break;
            case "wincache":
                return self::wincache_exist($name);
                break;
            case "apc":
                return self::apc_exist($name);
                break;
            case "xcache":
                return self::xcache_exist($name);
                break;
            default:
                return self::pdo_exist($name);
                break;
        }

    }

    public static function deleteMulti($object = array()) {
        $res = array();
        foreach($object as $driver=>$name)  {
            if(!is_numeric($driver)) {
                $n = $driver."_".$name;
                $name = array($driver=>$name);
            } else {
                $n = $name;
            }
            $res[$n] = self::delete($name);
        }
        return $res;

    }

    public static function setMulti($mname = array(), $time_in_second_for_all = 600, $skip_for_all = false) {
        $res = array();

        foreach($mname as $object){
            //   print_r($object);

            $keys = array_keys($object);

            if($keys[0] != "0") {
                $k = $keys[0];
                $name = isset($object[$k]) ? array($k => $object[$k]) : "";
                $n = $k."_".$object[$k];
                $x=0;
            } else {
                $name = isset($object[0]) ? $object[0] : "";
                $x=1;
                $n = $name;
            }

            $value = isset($object[$x]) ? $object[$x] : "";$x++;
            $time = isset($object[$x]) ? $object[$x] : $time_in_second_for_all;$x++;
            $skip = isset($object[$x]) ? $object[$x] : $skip_for_all;$x++;

            if($name!="" && $value!="") {
                $res[$n] = self::set($name,$value, $time, $skip);
            }
            // echo "<br> ----- <br>";

        }

        return $res;
    }



    public static function set($name,$value,$time_in_second = 600, $skip_if_existing = false) {
        $api = self::autoconfig($name);
        if(self::$useTmpCache == true) {
            $tmp_name = md5(serialize($api.$name));
            self::$Tmp[$tmp_name] = $value;
        }

        switch ($api) {
            case "pdo":
                return self::pdo_set($name,$value,$time_in_second, $skip_if_existing);
                break;
            case "mpdo":
                return self::pdo_set($name,$value,$time_in_second, $skip_if_existing);
                break;
            case "files":
                return self::files_set($name,$value,$time_in_second, $skip_if_existing);
                break;
            case "memcache":
                return self::memcache_set($name,$value,$time_in_second, $skip_if_existing);
                break;
            case "memcached":
                return self::memcached_set($name,$value,$time_in_second, $skip_if_existing);
                break;
            case "wincache":
                return self::wincache_set($name,$value,$time_in_second, $skip_if_existing);
                break;
            case "apc":
                return self::apc_set($name,$value,$time_in_second, $skip_if_existing);
                break;
            case "xcache":
                return self::xcache_set($name,$value,$time_in_second, $skip_if_existing);
                break;
            default:
                return self::pdo_set($name,$value,$time_in_second, $skip_if_existing);
                break;
        }

    }






    public static function decrement($name, $step = 1) {
        $api = self::autoconfig($name);
        if(self::$useTmpCache == true) {
            $tmp_name = md5(serialize($api.$name));
            if(isset(self::$Tmp[$tmp_name])) {
                self::$Tmp[$tmp_name] = (Int)self::$Tmp[$tmp_name] - $step;
            } else {
                self::$Tmp[$tmp_name] = $step;
            }

        }
        switch ($api) {
            case "pdo":
                return self::pdo_decrement($name, $step);
                break;
            case "mpdo":
                return self::pdo_decrement($name, $step);
                break;
            case "files":
                return self::files_decrement($name, $step);
                break;
            case "memcache":
                return self::memcache_decrement($name, $step);
                break;
            case "memcached":
                return self::memcached_decrement($name, $step);
                break;
            case "wincache":
                return self::wincache_decrement($name, $step);
                break;
            case "apc":
                return self::apc_decrement($name, $step);
                break;
            case "xcache":
                return self::xcache_decrement($name, $step);
                break;
            default:
                return self::pdo_decrement($name, $step);
                break;
        }
    }



    public static function get($name) {
        $api = self::autoconfig($name);
        if(self::$useTmpCache == true) {
            $tmp_name = md5(serialize($api.$name));
            if(isset(self::$Tmp[$tmp_name])) {
                return self::$Tmp[$tmp_name];
            }
        }

        // self::startDebug($api,"API",__LINE__,__FUNCTION__);

        // for files, check it if NULL and "empty" string
        switch ($api) {
            case "pdo":
                return self::pdo_get($name);
                break;
            case "mpdo":
                return self::pdo_get($name);

                break;
            case "files":
                return  self::files_get($name);
                break;
            case "memcache":
                return self::memcache_get($name);
                break;
            case "memcached":
                return self::memcached_get($name);
                break;
            case "wincache":
                return  self::wincache_get($name);
                break;
            case "apc":
                return  self::apc_get($name);
                break;
            case "xcache":
                return   self::xcache_get($name);
                break;
            default:
                return  self::pdo_get($name);
                break;
        }

    }


    public static function getMulti($object = array()) {
        $res = array();
        foreach($object as $driver=>$name)  {
            if(!is_numeric($driver)) {
                $n = $driver."_".$name;
                $name = array($driver=>$name);
            } else {
                $n = $name;
            }
            $res[$n] = self::get($name);
        }
        return $res;

    }



    public static function stats() {
        $api = self::autoconfig();
        switch ($api) {
            case "pdo":
                return self::pdo_stats();
                break;
            case "mpdo":
                return self::pdo_stats();
                break;
            case "files":
                return self::files_stats();
                break;
            case "memcache":
                return self::memcache_stats();
                break;
            case "memcached":
                return self::memcached_stats();
                break;
            case "wincache":
                return self::wincache_stats();
                break;
            case "apc":
                return self::apc_stats();
                break;
            case "xcache":
                return self::xcache_stats();
                break;
            default:
                return self::pdo_stats();
                break;
        }
    }

    public static function increment($name, $step = 1) {
        $api = self::autoconfig($name);

        if(self::$useTmpCache == true) {
            $tmp_name = md5(serialize($api.$name));
            if(isset(self::$Tmp[$tmp_name])) {
                self::$Tmp[$tmp_name] = (Int)self::$Tmp[$tmp_name] + $step;
            } else {
                self::$Tmp[$tmp_name] = $step;
            }

        }

        switch ($api) {
            case "pdo":
                return self::pdo_increment($name, $step);
                break;
            case "mpdo":
                return self::pdo_increment($name, $step);
                break;
            case "files":
                return self::files_increment($name, $step);
                break;
            case "memcache":
                return self::memcache_increment($name, $step);
                break;
            case "memcached":
                return self::memcached_increment($name, $step);
                break;
            case "wincache":
                return self::wincache_increment($name, $step);
                break;
            case "apc":
                return self::apc_increment($name, $step);
                break;
            case "xcache":
                return self::xcache_increment($name, $step);
                break;
            default:
                return self::pdo_increment($name, $step);
                break;
        }
    }


    /*
     * Begin FILES Cache Static
     * Use Files & Folders to cache
     */

    private static function files_exist($name) {
        $data = self::files_get($name);
        if($data == null) {
            return false;
        } else {
            return true;
        }
    }



    private static function files_set($name,$value,$time_in_second = 600, $skip_if_existing = false) {

        $db = self::selectDB($name);
        $name = $db['item'];
        $folder = $db['db'];

        $path = self::getPath();
        $tmp = explode("/",$folder);
        foreach($tmp as $dir) {
            if($dir!="" && $dir !="." && $dir!="..") {
                $path.="/".$dir;
                if(!file_exists($path)) {
                    mkdir($path,0777);
                }
            }
        }

        $file = $path."/".$name.".c.html";

        $write = true;
        if(file_exists($file)) {
            $data = self::decode(file_get_contents($file));
            if($skip_if_existing == true && ((Int)$data['time'] + (Int)$data['endin'] > @date("U")) ) {
                $write = false;
            }
        }

        if($write == true ) {
            try {
                $f = fopen($file,"w+");
                fwrite($f,self::encode($value,$time_in_second));
                fclose($f);
            } catch (Exception $e) {
                die("Sorry, can't write cache to file :".$file );
            }
        }

        return $value;
    }

    private static function files_get($name) {
        $db = self::selectDB($name);
        $name = $db['item'];
        $folder = $db['db'];

        $path = self::getPath();
        $tmp = explode("/",$folder);
        foreach($tmp as $dir) {
            if($dir!="" && $dir !="." && $dir!="..") {
                $path.="/".$dir;
            }
        }

        $file = $path."/".$name.".c.html";

        if(!file_exists($file)) {
            return null;
        }

        $data = self::decode(file_get_contents($file));

        if(!isset($data['time']) || !isset($data['endin']) || !isset($data['value'])) {
            return null;
        }

        if($data['time'] + $data['endin'] < @date("U")) {
            // exp
            unlink($file);
            return null;
        }

        return isset($data['value']) ? $data['value'] : null;
    }

    private static function files_stats($dir = "") {
        $total = array(
            "expired"   =>  0,
            "size"      =>  0,
            "files"     =>  0
        );
        if($dir == "") {
            $dir = self::getPath();
        }
        $d = opendir($dir);
        while($file = readdir($d))
        {
            if($file!="." && $file != "..") {
                $path = $dir."/".$file;
                if(is_dir($path)) {
                    $in = self::files_stats($path);
                    $total['expired'] = $total['expired'] + $in['expired'];
                    $total['size'] = $total['size'] + $in['size'];
                    $total['files'] = $total['files'] + $in['files'];
                }

                elseif(strpos($path,".c.html")!== false) {
                    $data = self::decode($path);
                    if(isset($data['value']) && isset($data['time']) && isset($data['endin'])) {
                        $total['files']++;
                        if($data['time'] + $data['endin'] < @date("U")) {
                            $total['expired']++;
                        }
                        $total['size'] = $total['size'] + filesize($path);
                    }
                }
            }

        }
        if($total['size'] > 0) {
            $total['size'] = $total['size']/1024/1024;
        }
        return $total;
    }

    private static function files_cleanup($dir = "") {
        $total = 0;
        if($dir == "") {
            $dir = self::getPath();
        }
        $d = opendir($dir);
        while($file = readdir($d))
        {
            if($file!="." && $file != "..") {
                $path = $dir."/".$file;
                if(is_dir($path)) {
                    $total = $total + self::files_cleanup($path);
                    try {
                        @unlink($path);
                    } catch (Exception $e) {
                        // nothing;
                    }
                }
                elseif(strpos($path,".c.html")!==false) {
                    $data = self::decode($path);
                    if(isset($data['value']) && isset($data['time']) && isset($data['endin'])) {
                        if((Int)$data['time'] + (Int)$data['endin'] < @date("U")) {
                            unlink($path);
                            $total++;
                        }
                    } else {
                        unlink($path);
                        $total++;
                    }
                }
            }

        }
        return $total;
    }

    private static function files_delete($name) {
        $db = self::selectDB($name);
        $name = $db['item'];
        $folder = $db['db'];

        $path = self::getPath();
        $tmp = explode("/",$folder);
        foreach($tmp as $dir) {
            if($dir!="" && $dir !="." && $dir!="..") {
                $path.="/".$dir;
            }
        }

        $file = $path."/".$name.".c.html";
        if(file_exists($file)) {
            try {
                unlink($file);
                return true;
            } catch(Exception $e) {
                return false;
            }

        }
        return true;
    }

    private static function files_increment($name, $step = 1) {
        $db = self::selectDB($name);
        $name = $db['item'];
        $folder = $db['db'];

        $path = self::getPath();
        $tmp = explode("/",$folder);
        foreach($tmp as $dir) {
            if($dir!="" && $dir !="." && $dir!="..") {
                $path.="/".$dir;
            }
        }

        $file = $path."/".$name.".c.html";
        if(!file_exists($file)) {
            self::files_set($name,$step,3600);
            return $step;
        }

        $data = self::decode(file_get_contents($file));
        if(isset($data['time']) && isset($data['value']) && isset($data['endin'])) {
            $data['value'] = $data['value'] + $step;
            self::files_set($name,$data['value'],$data['endin']);
        }
        return $data['value'];
    }

    private static function files_decrement($name, $step = 1) {
        $db = self::selectDB($name);
        $name = $db['item'];
        $folder = $db['db'];

        $path = self::getPath();
        $tmp = explode("/",$folder);
        foreach($tmp as $dir) {
            if($dir!="" && $dir !="." && $dir!="..") {
                $path.="/".$dir;
            }
        }

        $file = $path."/".$name.".c.html";
        if(!file_exists($file)) {
            self::files_set($name,$step,3600);
            return $step;
        }

        $data = self::decode(file_get_contents($file));
        if(isset($data['time']) && isset($data['value']) && isset($data['endin'])) {
            $data['value'] = $data['value'] - $step;
            self::files_set($name,$data['value'],$data['endin']);
        }
        return $data['value'];
    }

    private static function getMemoryName($name) {
        $db = self::selectDB($name);
        $name = $db['item'];
        $folder = $db['db'];
        $name = $folder."_".$name;

        // connect memory server
        if(self::$sys['method'] == "memcache" || $db['db'] == "memcache") {
            self::memcache_addserver();
        }elseif(self::$sys['method'] == "memcached" || $db['db'] == "memcached") {
            self::memcached_addserver();
        }elseif(self::$sys['method'] == "wincache") {
            // init WinCache here

        }

        return $name;
    }


    /*
     * Begin XCache Static
     * http://xcache.lighttpd.net/wiki/XcacheApi
     */

    private static function xcache_exist($name) {
        $name = self::getMemoryName($name);
        if(xcache_isset($name)) {
            return true;
        } else {
            return false;
        }
    }


    private static function xcache_set($name,$value,$time_in_second = 600, $skip_if_existing = false) {
        $name = self::getMemoryName($name);
        if($skip_if_existing == true) {
            if(!self::xcache_exist($name)) {
                return xcache_set($name,$value,$time_in_second);
            }
        } else {
            return xcache_set($name,$value,$time_in_second);
        }
        return false;
    }

    private static function xcache_get($name) {

        $name = self::getMemoryName($name);

        $data = xcache_get($name);

        if($data === false || $data == "") {
            return null;
        }
        return $data;

    }

    private static function xcache_stats() {
        try {
            return xcache_list(XC_TYPE_VAR,100);
        } catch(Exception $e) {
            return array();
        }
    }

    private static function xcache_cleanup($option = array()) {
        // Revision 621

        $cnt = xcache_count(XC_TYPE_VAR);
        for ($i=0; $i < $cnt; $i++) {
            xcache_clear_cache(XC_TYPE_VAR, $i);
        }
        return true;
    }

    private static function xcache_delete($name) {
        $name = self::getMemoryName($name);
        return xcache_unset($name);
    }

    private static function xcache_increment($name, $step = 1) {
        $orgi = $name;
        $name = self::getMemoryName($name);
        $ret =xcache_inc($name, $step);
        if($ret === false) {
            self::xcache_set($orgi,$step,3600);
            return $step;
        } else {
            return $ret;
        }
    }

    private static function xcache_decrement($name, $step = 1) {
        $orgi = $name;
        $name = self::getMemoryName($name);
        $ret = xcache_dec($name, $step);
        if($ret === false) {
            self::xcache_set($orgi,$step,3600);
            return $step;
        } else {
            return $ret;
        }
    }


    /*
     * Begin APC Static
     * http://www.php.net/manual/en/ref.apc.php
     */

    private static function apc_exist($name) {
        $name = self::getMemoryName($name);
        if(apc_exists($name)) {
            return true;
        } else {
            return false;
        }
    }


    private static function apc_set($name,$value,$time_in_second = 600, $skip_if_existing = false) {
        $name = self::getMemoryName($name);
        if($skip_if_existing == true) {
            return apc_add($name,$value,$time_in_second);
        } else {
            return apc_store($name,$value,$time_in_second);
        }
    }

    private static function apc_get($name) {

        $name = self::getMemoryName($name);

        $data = apc_fetch($name,$bo);

        if($bo === false) {
            return null;
        }
        return $data;

    }

    private static function apc_stats() {
        try {
            return apc_cache_info("user");
        } catch(Exception $e) {
            return array();
        }
    }

    private static function apc_cleanup($option = array()) {
        return apc_clear_cache("user");
    }

    private static function apc_delete($name) {
        $name = self::getMemoryName($name);
        return apc_delete($name);
    }

    private static function apc_increment($name, $step = 1) {
        $orgi = $name;
        $name = self::getMemoryName($name);
        $ret = apc_inc($name, $step, $fail);
        if($ret === false) {
            self::apc_set($orgi,$step,3600);
            return $step;
        } else {
            return $ret;
        }
    }

    private static function apc_decrement($name, $step = 1) {
        $orgi = $name;
        $name = self::getMemoryName($name);
        $ret = apc_dec($name, $step, $fail);
        if($ret === false) {
            self::apc_set($orgi,$step,3600);
            return $step;
        } else {
            return $ret;
        }
    }


    /*
     * Begin Memcache Static
     * http://www.php.net/manual/en/class.memcache.php
     */
    public static function memcache_addserver() {
        if(!isset(self::$checked['memcache'])) {
            self::$checked['memcache'] = array();
        }

        if(self::$objects['memcache'] == "") {
            self::$objects['memcache'] = new Memcache();

            foreach(self::$server as $server) {
                $name = isset($server[0]) ? $server[0] : "";
                $port = isset($server[1]) ? $server[1] : 11211;
                if(!in_array($server, self::$checked['memcache']) && $name !="") {
                    self::$objects['memcache']->addServer($name,$port);
                    self::$checked['memcache'][] = $name;
                }
            }

        }

    }



    private static function memcache_exist($name) {
        $x = self::memcache_get($name);
        if($x == null) {
            return false;
        } else {
            return true;
        }
    }






    private static function memcache_set($name,$value,$time_in_second = 600, $skip_if_existing = false) {
        $orgi = $name;
        $name = self::getMemoryName($name);
        if($skip_if_existing == false) {
            return self::$objects['memcache']->set($name, $value, false, $time_in_second );
        } else {
            return self::$objects['memcache']->add($name, $value, false, $time_in_second );
        }

    }

    private static function memcache_get($name) {
        $name = self::getMemoryName($name);
        $x = self::$objects['memcache']->get($name);
        if($x == false) {
            return null;
        } else {
            return $x;
        }
    }

    private static function memcache_stats() {
        self::memcache_addserver();
        return self::$objects['memcache']->getStats();
    }

    private static function memcache_cleanup($option = "") {
        self::memcache_addserver();
        self::$objects['memcache']->flush();
        return true;
    }

    private static function memcache_delete($name) {
        $name = self::getMemoryName($name);
        return self::$objects['memcache']->delete($name);
    }

    private static function memcache_increment($name, $step = 1) {
        $name = self::getMemoryName($name);
        return self::$objects['memcache']->increment($name, $step);
    }

    private static function memcache_decrement($name, $step = 1) {
        $name = self::getMemoryName($name);
        return self::$objects['memcache']->decrement($name, $step);
    }



    /*
     * Begin Memcached Static
     */

    public static function memcached_addserver() {
        if(!isset(self::$checked['memcached'])) {
            self::$checked['memcached'] = array();
        }

        if(self::$objects['memcached'] == "") {
            self::$objects['memcached'] = new Memcached();

            foreach(self::$server as $server) {
                $name = isset($server[0]) ? $server[0] : "";
                $port = isset($server[1]) ? $server[1] : 11211;
                $sharing = isset($server[2]) ? $server[2] : 0;
                if(!in_array($server, self::$checked['memcached']) && $name !="") {
                    if($sharing >0 ) {
                        self::$objects['memcached']->addServer($name,$port,$sharing);
                    } else {
                        self::$objects['memcached']->addServer($name,$port);
                    }

                    self::$checked['memcached'][] = $name;
                }
            }

        }
    }


    private static function memcached_exist($name) {
        $x = self::memcached_get($name);
        if($x == null) {
            return false;
        } else {
            return true;
        }
    }



    private static function memcached_set($name,$value,$time_in_second = 600, $skip_if_existing = false) {
        $orgi = $name;
        $name = self::getMemoryName($name);
        if($skip_if_existing == false) {
            return self::$objects['memcached']->set($name, $value, time() + $time_in_second );
        } else {
            return self::$objects['memcached']->add($name, $value, time() + $time_in_second );
        }

    }

    private static function memcached_get($name) {
        $name = self::getMemoryName($name);
        $x = self::$objects['memcached']->get($name);
        if($x == false) {
            return null;
        } else {
            return $x;
        }
    }

    private static function memcached_stats() {
        self::memcached_addserver();
        return self::$objects['memcached']->getStats();
    }

    private static function memcached_cleanup($option = "") {
        self::memcached_addserver();
        self::$objects['memcached']->flush();
        return true;
    }

    private static function memcached_delete($name) {
        $name = self::getMemoryName($name);
        return self::$objects['memcached']->delete($name);
    }

    private static function memcached_increment($name, $step = 1) {
        $name = self::getMemoryName($name);
        return self::$objects['memcached']->increment($name, $step);
    }

    private static function memcached_decrement($name, $step = 1) {
        $name = self::getMemoryName($name);
        return self::$objects['memcached']->decrement($name, $step);
    }

    /*
     * Begin WinCache Static
     */

    private static function wincache_exist($name) {
        $name = self::getMemoryName($name);
        if(wincache_ucache_exists($name)) {
            return true;
        } else {
            return false;
        }
    }




    private static function wincache_set($name,$value,$time_in_second = 600, $skip_if_existing = false) {
        $orgi = $name;
        $name = self::getMemoryName($name);
        if($skip_if_existing == false) {
            return wincache_ucache_set($name, $value, $time_in_second );
        } else {
            return wincache_ucache_add($name, $value, $time_in_second );
        }

    }

    private static function wincache_get($name) {
        $name = self::getMemoryName($name);

        $x = wincache_ucache_get($name,$suc);

        if($suc == false) {
            return null;
        } else {
            return $x;
        }
    }

    private static function wincache_stats() {
        return wincache_scache_info();
    }

    private static function wincache_cleanup($option = "") {
        wincache_ucache_clear();
        return true;
    }

    private static function wincache_delete($name) {
        $name = self::getMemoryName($name);
        return wincache_ucache_delete($name);
    }

    private static function wincache_increment($name, $step = 1) {
        $name = self::getMemoryName($name);
        return wincache_ucache_inc($name, $step);
    }

    private static function wincache_decrement($name, $step = 1) {
        $name = self::getMemoryName($name);
        return wincache_ucache_dec($name, $step);
    }


    /*
     * Begin PDO Static
     */

    private static function pdo_exist($name) {
        $db = self::selectDB($name);
        $name = $db['item'];

        $x = self::db(array('db'=>$db['db']))->prepare("SELECT COUNT(*) as `total` FROM ".self::$table." WHERE `name`=:name");

        $x->execute(array(
            ":name" => $name,
        ));

        $row = $x->fetch(PDO::FETCH_ASSOC);
        if($row['total'] >0 ){
            return true;
        } else {
            return false;
        }

    }


    private static function pdo_cleanup($option = "") {
        self::db(array("skip_clean" => true))->exec("drop table if exists ".self::$table);
        self::initDatabase();
        return true;
    }

    private static function pdo_stats($full = false) {
        $res = array();
        if($full == true) {
            $stm = self::db()->prepare("SELECT * FROM ".self::$table."");
            $stm->execute();
            $result = $stm->fetchAll();
            $res['data'] = $result;
        }
        $stm = self::db()->prepare("SELECT COUNT(*) as `total` FROM ".self::$table."");
        $stm->execute();
        $result = $stm->fetch();
        $res['record'] = $result['total'];
        if(self::$path!="memory") {
            $res['size'] = filesize(self::getPath()."/".self::$filename);
        }

        return $res;
    }


    // for PDO return DB name,
    // For Files, return Dir
    private static function selectDB($object) {
        $res = array(
            'db'    => "",
            'item'  => "",
        );
        if(is_array($object)) {
            $key = array_keys($object);
            $key = $key[0];
            $res['db'] = $key;
            $res['item'] = self::safename($object[$key]);
        } else {
            $res['item'] = self::safename($object);
        }

        if($res['db'] == "" && self::$sys['method'] == "files") {
            $res['db'] = "files";
        }

        // for auto database
        if($res['db'] == "" && self::$storage== "mpdo") {
            $create_table = false;
            if(!file_exists('sqlite:'.self::getPath().'/phpfastcache.c')) {
                $create_table = true;
            }
            if(self::$autodb == "") {
                try {
                    self::$autodb = new PDO('sqlite:'.self::getPath().'/phpfastcache.c');
                    self::$autodb->setAttribute(PDO::ATTR_ERRMODE,
                        PDO::ERRMODE_EXCEPTION);

                } catch (PDOexception  $e) {
                    die("Please CHMOD 0777 or Writable Permission for ".self::getPath());
                }

            }

            if($create_table == true) {
                self::$autodb->exec('CREATE TABLE IF NOT EXISTS "main"."db" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL  UNIQUE , "item" VARCHAR NOT NULL  UNIQUE , "dbname" INTEGER NOT NULL )');
            }

            $db = self::$autodb->prepare("SELECT * FROM `db` WHERE `item`=:item");
            $db->execute(array(
                ":item" => $res['item'],
            ));
            $row = $db->fetch(PDO::FETCH_ASSOC);
            if(isset($row['dbname'])) {
                // found key
                $res['db'] = $row['dbname'];
            } else {
                // not key // check filesize
                if((Int)self::$autosize < 10) {
                    self::$autosize = 10;
                }
                // get last key
                $db = self::$autodb->prepare("SELECT * FROM `db` ORDER BY `id` DESC");
                $db->execute();
                $row = $db->fetch(PDO::FETCH_ASSOC);
                $dbname = isset($row['dbname']) ? $row['dbname'] : 1;
                $fsize = file_exists(self::getPath()."/".$dbname.".cache") ? filesize(self::getPath()."/".$dbname.".cache") : 0;
                if($fsize > (1024*1024*(Int)self::$autosize)) {
                    $dbname = (Int)$dbname + 1;
                }
                try {
                    $insert = self::$autodb->prepare("INSERT INTO `db` (`item`,`dbname`) VALUES(:item,:dbname)");
                    $insert->execute(array(
                        ":item" => $res['item'],
                        ":dbname"   => $dbname
                    ));
                } catch (PDOexception  $e) {
                    die('Database Error - Check A look at self::$autodb->prepare("INSERT INTO ');
                }

                $res['db'] = $dbname;

            }
        }

        return $res;

    }

    private static function pdo_get($name) {
        $db = self::selectDB($name);
        $name = $db['item'];
        // array('db'=>$db['db'])

        // self::startDebug($db,"",__LINE__,__FUNCTION__);

        $stm = self::db(array('db'=>$db['db']))->prepare("SELECT * FROM ".self::$table." WHERE `name`='".$name."'");
        $stm->execute();
        $res = $stm->fetch(PDO::FETCH_ASSOC);

        if(!isset($res['value'])) {
            return null;
        } elseif((Int)$res['added'] + (Int)$res['endin'] <= (Int)@date("U")) {
            return null;
        } else {
            // decode value on SQL;
            $data = self::decode($res['value']);
            // check if VALUE on string encode
            return isset($data['value']) ? $data['value'] : null;
        }
    }

    private static function pdo_decrement($name, $step = 1) {
        $db = self::selectDB($name);
        $name = $db['item'];
        // array('db'=>$db['db'])

        $int = self::get($name);
        try {
            $stm = self::db(array('db'=>$db['db']))->prepare("UPDATE ".self::$table." SET `value`=:new WHERE `name`=:name ");
            $stm->execute(array(
                ":new"  => self::encode($int - $step),
                ":name" =>  $name,
            ));

        } catch (PDOexception  $e) {
            die("Sorry! phpFastCache don't allow this type of value - Name: ".$name." -> Decrement: ".$step);
        }
        return $int - $step;

    }

    private static function pdo_increment($name ,$step = 1) {
        $db = self::selectDB($name);
        $name = $db['item'];
        // array('db'=>$db['db'])

        $int = self::get($name);
        // echo $int."xxx";
        try {
            $stm = self::db(array('db'=>$db['db']))->prepare("UPDATE ".self::$table." SET `value`=:new WHERE `name`=:name ");
            $stm->execute(array(
                ":new" => self::encode($int + $step),
                ":name" =>  $name,
            ));

        } catch (PDOexception  $e) {
            die("Sorry! phpFastCache don't allow this type of value - Name: ".$name." -> Increment: ".$step);
        }
        return $int + $step;

    }

    private static function pdo_delete($name) {
        $db = self::selectDB($name);
        $name = $db['item'];

        return self::db(array('db'=>$db['db']))->exec("DELETE FROM ".self::$table." WHERE `name`='".$name."'");
    }

    private static function pdo_set($name,$value,$time_in_second = 600, $skip_if_existing = false) {
        $db = self::selectDB($name);
        $name = $db['item'];
        // array('db'=>$db['db'])

        if($skip_if_existing == true) {
            try {
                $insert = self::db(array('db'=>$db['db']))->prepare("INSERT OR IGNORE INTO ".self::$table." (name,value,added,endin) VALUES(:name,:value,:added,:endin)");
                try {
                    $value = self::encode($value);
                } catch(Exception $e) {
                    die("Sorry! phpFastCache don't allow this type of value - Name: ".$name);
                }

                $insert->execute(array(
                    ":name"  => $name,
                    ":value"    => $value,
                    ":added"    => @date("U"),
                    ":endin"  =>  (Int)$time_in_second
                ));

                return true;
            } catch (PDOexception  $e) {
                return false;
            }

        } else {
            try {
                $insert = self::db(array('db'=>$db['db']))->prepare("INSERT OR REPLACE INTO ".self::$table." (name,value,added,endin) VALUES(:name,:value,:added,:endin)");
                try {
                    $value = self::encode($value);
                } catch(Exception $e) {
                    die("Sorry! phpFastCache don't allow this type of value - Name: ".$name);
                }

                $insert->execute(array(
                    ":name"  => $name,
                    ":value"    => $value,
                    ":added"    => @date("U"),
                    ":endin"  =>  (Int)$time_in_second
                ));

                return true;
            } catch (PDOexception  $e) {
                return false;
            }
        }
    }



    private static function db($option = array()) {
        $vacuum = false;
        $dbname = isset($option['db']) ? $option['db'] : "";
        $dbname = $dbname != "" ? $dbname : self::$filename;
        if($dbname!=self::$filename) {
            $dbname = $dbname.".cache";
        }
        // debuging
        // self::startDebug(self::$storage,"Check Storage",__LINE__,__FUNCTION__);
        $initDB = false;

        if(self::$storage == "pdo") {
            // start self PDO
            if(self::$objects['pdo']=="") {

                //  self::$objects['pdo'] == new PDO("sqlite:".self::$path."/cachedb.sqlite");
                if(!file_exists(self::getPath()."/".$dbname)) {
                    $initDB = true;
                } else {
                    if(!is_writable(self::getPath()."/".$dbname)) {
                        @chmod(self::getPath()."/".$dbname,0777);
                        if(!is_writable(self::getPath()."/".$dbname)) {
                            die("Please CHMOD 0777 or any Writable Permission for ".self::getPath()."/".$dbname);
                        }
                    }
                }



                try {
                    self::$objects['pdo'] = new PDO("sqlite:".self::getPath()."/".$dbname);
                    self::$objects['pdo']->setAttribute(PDO::ATTR_ERRMODE,
                        PDO::ERRMODE_EXCEPTION);

                    if($initDB == true) {
                        self::initDatabase();
                    }

                    $time = filemtime(self::getPath()."/".$dbname);
                    if($time + (3600*24) < @date("U")) {
                        $vacuum = true;
                    }

                    // Revision 619
                    // auto Vaccuum() every 48 hours
                    if($vacuum == true) {
                        if(!isset($option['skip_clean'])) {
                            self::$objects['pdo']->exec("DELETE FROM ".self::$table." WHERE (`added` + `endin`) < ".@date("U"));
                        }
                        self::$objects['pdo']->exec('VACUUM');
                    }



                } catch (PDOexception  $e) {
                    die("Can't connect to caching file ".self::getPath()."/".$dbname);
                }






                return self::$objects['pdo'];

            } else {
                return self::$objects['pdo'];
            }
            // end self pdo

        } elseif(self::$storage == "mpdo") {

            // start self PDO
            if(!isset(self::$multiPDO[$dbname])) {
                //  self::$objects['pdo'] == new PDO("sqlite:".self::$path."/cachedb.sqlite");
                if(self::$path!="memory") {
                    if(!file_exists(self::getPath()."/".$dbname)) {
                        $initDB = true;
                    } else {
                        if(!is_writable(self::getPath()."/".$dbname)) {
                            @chmod(self::getPath()."/".$dbname,0777);
                            if(!is_writable(self::getPath()."/".$dbname)) {
                                die("Please CHMOD 0777 or any Writable Permission for PATH ".self::getPath());
                            }
                        }
                    }



                    try {
                        self::$multiPDO[$dbname] = new PDO("sqlite:".self::getPath()."/".$dbname);
                        self::$multiPDO[$dbname]->setAttribute(PDO::ATTR_ERRMODE,
                            PDO::ERRMODE_EXCEPTION);

                        if($initDB == true) {
                            self::initDatabase(self::$multiPDO[$dbname]);
                        }

                        $time = filemtime(self::getPath()."/".$dbname);
                        if($time + (3600*24) < @date("U")) {
                            $vacuum = true;
                        }

                        // Revision 619
                        if($vacuum == true) {
                            if(!isset($option['skip_clean'])) {
                                self::$multiPDO[$dbname]->exec("DELETE FROM ".self::$table." WHERE (`added` + `endin`) < ".@date("U"));
                            }
                            self::$multiPDO[$dbname]->exec('VACUUM');
                        }

                    } catch (PDOexception  $e) {
                        // Revision 619
                       die("Can't connect to caching file ".self::getPath()."/".$dbname);
                    }


                }




                return self::$multiPDO[$dbname];

            } else {
                return self::$multiPDO[$dbname];
            }
            // end self pdo

        }





    }

    private static function initDatabase($object = null) {
        if($object == null) {
            self::db(array("skip_clean" => true))->exec('CREATE TABLE IF NOT EXISTS "'.self::$table.'" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL  UNIQUE , "name" VARCHAR UNIQUE NOT NULL  , "value" BLOB, "added" INTEGER NOT NULL  DEFAULT 0, "endin" INTEGER NOT NULL  DEFAULT 0)');
            self::db(array("skip_clean" => true))->exec('CREATE INDEX "lookup" ON "'.self::$table.'" ("added" ASC, "endin" ASC)');
            self::db(array("skip_clean" => true))->exec('VACUUM');
        } else {
            $object->exec('CREATE TABLE IF NOT EXISTS "'.self::$table.'" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL  UNIQUE , "name" VARCHAR UNIQUE NOT NULL  , "value" BLOB, "added" INTEGER NOT NULL  DEFAULT 0, "endin" INTEGER NOT NULL  DEFAULT 0)');
            $object->exec('CREATE INDEX "lookup" ON "'.self::$table.'" ("added" ASC, "endin" ASC)');
            $object->exec('VACUUM');
        }
    }

    // send all bugs to my email
    // you can replace it to your email
    // maximum 1 email per hour
    // you can use phpFastCache::bugs($title, $e) in any code
    public static function bugs($title, $e) {
        $code = md5("error_".$title);
        $send = self::get($code);
        if($send == null) {
            $to = "khoaofgod@yahoo.com";
            $subject = "Bugs: ".$title;
            $message = "Error Serialize:".serialize($e);
            $from = "root@".$_SERVER['HTTP_HOST'];
            $headers = "From:" . $from;
            @mail($to,$subject,$message,$headers);
            self::set($code,1,3600);
        }
    }

    // use for debug
    // public function, you can use phpFastCache::debug($e|array|string) any time in any code
    public static function debug($e, $exit = false) {
        echo "<pre>";
        print_r($e);
        echo "</pre>";
        if($exit == true) {
            exit;
        }
    }

    public static function startDebug($value,$text = "", $line = __LINE__, $func = __FUNCTION__) {
        if(self::$debugging == true) {
            self::$step_debugging++;
            if(!is_array($value)) {
                echo "<br>".self::$step_debugging." => ".$line." | ".$func." | ".$text." | ".$value;
            } else {
                echo "<br>".self::$step_debugging." => ".$line." | ".$func." | ".$text." | ";
                print_r($value);
            }

        }
    }

}


