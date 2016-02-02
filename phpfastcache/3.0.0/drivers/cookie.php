<?php

/**
 * Class phpfastcache_cookie
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */
class phpfastcache_cookie extends BasePhpFastCache implements phpfastcache_driver
{
    /**
     * phpfastcache_cookie constructor.
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->setup($config);
        if (!$this->checkdriver() && !isset($config[ 'skipError' ])) {
            $this->fallback = true;
        }
        if (class_exists("Redis")) {
            $this->instant = new Redis();
        }

    }

    /**
     * @return bool
     */
    public function checkdriver()
    {
        // Check memcache
        if (function_exists("setcookie")) {
            return true;
        }
        $this->fallback = true;
        return false;
    }

    /**
     *
     */
    public function connectServer()
    {
        // for cookie check output
        if (!isset($_COOKIE[ 'phpfastcache' ])) {
            if (!@setcookie("phpfastcache", 1, 10)) {
                $this->fallback = true;
            }
        }

    }

    /**
     * @param $keyword
     * @param string $value
     * @param int $time
     * @param array $option
     * @return bool
     */
    public function driver_set($keyword, $value = "", $time = 300, $option = array())
    {
        $this->connectServer();
        $keyword = "phpfastcache_" . $keyword;
        return @setcookie($keyword, $this->encode($value), $time, "/");

    }

    /**
     * @param $keyword
     * @param array $option
     * @return bool|mixed|null
     */
    public function driver_get($keyword, $option = array())
    {
        $this->connectServer();
        // return null if no caching
        // return value if in caching
        $keyword = "phpfastcache_" . $keyword;
        $x = isset($_COOKIE[ $keyword ]) ? $this->decode($_COOKIE[ 'keyword' ]) : false;
        if ($x == false) {
            return null;
        } else {
            return $x;
        }
    }

    /**
     * @param $keyword
     * @param array $option
     */
    public function driver_delete($keyword, $option = array())
    {
        $this->connectServer();
        $keyword = "phpfastcache_" . $keyword;
        @setcookie($keyword, null, -10);
        $_COOKIE[ $keyword ] = null;
    }

    /**
     * @param array $option
     * @return array
     */
    public function driver_stats($option = array())
    {
        $this->connectServer();
        $res = array(
          "info" => "",
          "size" => "",
          "data" => $_COOKIE,
        );

        return $res;

    }

    /**
     * @param array $option
     */
    public function driver_clean($option = array())
    {
        $this->connectServer();
        foreach ($_COOKIE as $keyword => $value) {
            if (strpos($keyword, "phpfastcache") !== false) {
                @setcookie($keyword, null, -10);
                $_COOKIE[ $keyword ] = null;
            }
        }
    }

    /**
     * @param $keyword
     * @return bool
     */
    public function driver_isExisting($keyword)
    {
        $this->connectServer();
        $x = $this->get($keyword);

        return !($x == null);
    }
}