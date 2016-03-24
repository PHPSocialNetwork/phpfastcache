<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace phpFastCache\Drivers;

use phpFastCache\Core\DriverAbstract;

/**
 * Class cookie
 * @package phpFastCache\Drivers
 */
class cookie extends DriverAbstract
{
    /**
     * phpFastCache_cookie constructor.
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->setup($config);
        if (!$this->checkdriver() && !isset($config[ 'skipError' ])) {
            $this->fallback = true;
        }
    }

    /**
     * @return bool
     */
    public function checkdriver()
    {
        // Check memcache
        if (function_exists('setcookie')) {
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
        if (!isset($_COOKIE[ 'phpFastCache' ])) {
            if (!@setcookie('phpFastCache', 1, 10)) {
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
    public function driver_set($keyword, $value = '', $time = 300, $option = array())
    {
        $this->connectServer();
        $keyword = 'phpFastCache_' . $keyword;
        $v = $this->encode($value);
        if(isset($this->config['limited_memory_each_object'])
            && strlen($v) > $this->config['limited_memory_each_object']) {
            return false;
        }
        return setcookie($keyword, $v, time() + ($time ? (int)$time : 300), '/');

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
        $keyword = 'phpFastCache_' . $keyword;
        $x = isset($_COOKIE[ $keyword ]) ? $this->decode($_COOKIE[ $keyword ]) : false;
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
        $keyword = 'phpFastCache_' . $keyword;
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
          'info' => '',
          'size' => '',
          'data' => $_COOKIE,
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
            if (strpos($keyword, 'phpFastCache') !== false) {
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