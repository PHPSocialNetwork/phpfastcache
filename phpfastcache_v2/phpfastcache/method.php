<?php

/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */



abstract class phpfastcache_method {
    var $checked = array(
        "path"  => false,
    );

    var $tmp = array();
    var $option = array();
    var $server = array(
            array("127.0.0.1",11211),
    );
    /*
     * Check if this Cache Method is available for server or not
     */
    abstract function __construct($option = array());
    public function setOption($option) {
        $this->option = array_merge($this->option, $option);
    }

    abstract function checkMethod();

    /*
     * check Existing cache
     * return true if exist and false if not
     */
    abstract function isExisting($keyword);

    /*
     * SET
     * set a obj to cache
     */
    abstract function set($keyword, $value = "", $time = 300, $option = array() );

    /*
     * GET
     * return null or value of cache
     */
    abstract function get($keyword, $option = array());

    /*
     * Stats
     * Show stats of caching
     * Return array ("info","size","data")
     */
    abstract function stats($option = array());

    /*
     * Delete
     * Delete a cache
     */
    abstract function delete($keyword, $option = array());

    /*
     * clean
     * Clean up whole cache
     */
    abstract function clean($option = array());

    /*
     * Increment
     */
    abstract function increment($keyword, $step = 1, $option = array());

    /*
     * Decrement
     */

    abstract function decrement($keyword, $step = 1,  $option = array());


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



    function option($name, $value = null) {
        if($value == null) {
            if(isset($this->option[$name])) {
                return $this->option[$name];
            } else {
                return null;
            }
        } else {
            $this->option[$name] = $value;
            return $this;
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
                @fwrite($f,$html);
                @fclose($f);
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
                    die("Sorry, Please create ".$this->option("path")."/".$this->option("securityKey")."/ and SET Mode 0777 or any Writable Permission!" );
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


}