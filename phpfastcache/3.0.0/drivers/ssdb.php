<?php

/**
 * @author: hidongnan@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 * 
 * ssdb official website:
 * http://ssdb.io/
 */

class phpfastcache_ssdb extends BasePhpFastCache implements phpfastcache_driver {

    private $checked_ssdb = false;

    function checkdriver() {
        // Check memcache
        $this->required_extension("SSDB.php");
        if (class_exists("SimpleSSDB")) {
            return true;
        }
        $this->fallback = true;
        return false;
    }

    function __construct($config = array()) {
        $this->setup($config);
        if (!$this->checkdriver() && !isset($config['skipError'])) {
            $this->fallback = true;
        }
    }

    function connectServer() {

        $server = isset($this->config['ssdb']) ? $this->config['ssdb'] : array(
                                                                                "host" => "127.0.0.1",
                                                                                "port" => 8888,
                                                                                "password" => "",
                                                                                "timeout" => 2000,
                                                                              );

        if ($this->checked_ssdb === false) {
            $host = $server['host'];
            $port = isset($server['port']) ? (Int) $server['port'] : 8888;
            $password = isset($server['password']) ? $server['password'] : "";
            $timeout = isset($server['timeout']) ? (Int) $server['timeout'] : 2000;
            $this->instant = new SimpleSSDB($host, $port, $timeout);
            if (!empty($password)) {
                $this->instant->auth($password);
            }
            $this->checked_ssdb = true;
            if (!$this->instant) {
                $this->fallback = true;
                return false;
            } else {
                return true;
            }
        }

        return true;
    }

    function driver_set($keyword, $value = "", $time = 300, $option = array()) {
        if ($this->connectServer()) {
	        if (isset($option['skipExisting']) && $option['skipExisting'] == true) {
		        $x = $this->instant->get($keyword);
                if($x === false) {
                    return false;
                }elseif(!is_null($x)) {
                    return true;
                }
	        }
            $value = $this->encode($value);
		    return $this->instant->setx($keyword, $value, $time);
        } else {
            return $this->backup()->set($keyword, $value, $time, $option);
        }
    }

    function driver_get($keyword, $option = array()) {
        if ($this->connectServer()) {
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
        if ($this->connectServer()) {
            $this->instant->del($keyword);
        }
    }

    function driver_stats($option = array()) {
        if ($this->connectServer()) {
            $res = array(
                "info" => "",
                "size" => $this->instant->dbsize(),
                "data" => $this->instant->info(),
            );

            return $res;
        }

        return array();
    }

    function driver_clean($option = array())
    {
        //Is not supported, only support command line operations
        return false;
    }

    function driver_isExisting($keyword)
    {
        if ($this->connectServer()) {
            $x = $this->instant->exists($keyword);
            if ($x == null) {
                return false;
            } else {
                return true;
            }
        } else {
            return $this->backup()->isExisting($keyword);
        }
    }
    
}
