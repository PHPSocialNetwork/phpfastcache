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
 * Class ssdb
 * @package phpFastCache\Drivers
 */
class ssdb extends DriverAbstract
{

    /**
     * @var bool
     */
    private $checked_ssdb = false;

    /**
     * phpFastCache_ssdb constructor.
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
        $this->required_extension('SSDB');
        if (class_exists('SimpleSSDB')) {
            return true;
        }
        $this->fallback = true;
        return false;
    }


    /**
     * @return bool
     */
    public function connectServer()
    {

        $server = isset($this->config[ 'ssdb' ]) ? $this->config[ 'ssdb' ] : array(
          'host' => "127.0.0.1",
          'port' => 8888,
          'password' => '',
          'timeout' => 2000,
        );

        if ($this->checked_ssdb === false) {
            $host = $server[ 'host' ];
            $port = isset($server[ 'port' ]) ? (int)$server[ 'port' ] : 8888;
            $password = isset($server[ 'password' ]) ? $server[ 'password' ] : '';
            $timeout = !empty($server[ 'timeout' ]) ? (int)$server[ 'timeout' ] : 2000;
            $this->instant = new \SimpleSSDB($host, $port, $timeout);
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

    /**
     * @param $keyword
     * @param string $value
     * @param int $time
     * @param array $option
     * @return bool
     */
    public function driver_set($keyword, $value = '', $time = 300, $option = array())
    {
        if ($this->connectServer()) {
            if (isset($option[ 'skipExisting' ]) && $option[ 'skipExisting' ] == true) {
                $x = $this->instant->get($keyword);
                if ($x === false) {
                    return false;
                } elseif (!is_null($x)) {
                    return true;
                }
            }
            $value = $this->encode($value);
            return $this->instant->setx($keyword, $value, $time);
        } else {
            return $this->backup()->set($keyword, $value, $time, $option);
        }
    }

    /**
     * @param $keyword
     * @param array $option
     * @return mixed|null
     */
    public function driver_get($keyword, $option = array())
    {
        if ($this->connectServer()) {
            // return null if no caching
            // return value if in caching'
            $x = $this->instant->get($keyword);
            if ($x == false) {
                return null;
            } else {
                return $this->decode($x);
            }
        } else {
            $this->backup()->get($keyword, $option);
        }
    }

    /**
     * @param $keyword
     * @param array $option
     */
    public function driver_delete($keyword, $option = array())
    {
        if ($this->connectServer()) {
            $this->instant->del($keyword);
        }
    }

    /**
     * @param array $option
     * @return array
     */
    public function driver_stats($option = array())
    {
        if ($this->connectServer()) {
            $res = array(
              'info' => '',
              'size' => $this->instant->dbsize(),
              'data' => $this->instant->info(),
            );

            return $res;
        }

        return array();
    }

    /**
     * @param array $option
     * @return bool
     */
    public function driver_clean($option = array())
    {
        //Is not supported, only support command line operations
        return false;
    }

    /**
     * @param $keyword
     * @return bool
     */
    public function driver_isExisting($keyword)
    {
        if ($this->connectServer()) {
            $x = $this->instant->exists($keyword);
            return !($x == null);
        } else {
            return $this->backup()->isExisting($keyword);
        }
    }
}
