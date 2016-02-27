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
use Predis\Client as PredisSoftware;

/**
 * Class predis
 * @package phpFastCache\Drivers
 */
class predis extends DriverAbstract
{

    /**
     * @var bool
     */
    public $checked_redis = false;

    /**
     * phpFastCache_predis constructor.
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->setup($config);
        if (!class_exists("\\Predis\\Client")) {
            $this->required_extension("predis-1.0/autoload");
        }
    }

    /**
     * @return bool
     */
    public function checkdriver()
    {
        // Check memcache
        if (!class_exists("\\Predis\\Client")) {
            $this->required_extension("predis-1.0/autoload");
            try {
                \Predis\Autoloader::register();
            } catch (\Exception $e) {

            }
        }
        return true;
    }


    /**
     * @return bool
     */
    public function connectServer()
    {

        $server = isset($this->config[ 'redis' ]) ? $this->config[ 'redis' ] : array(
          'host' => '127.0.0.1',
          'port' => '6379',
          'password' => '',
          'database' => '',
        );


        if ($this->checked_redis === false) {
            $c = array(
              'host' => $server[ 'host' ],
            );

            $port = isset($server[ 'port' ]) ? $server[ 'port' ] : '';
            if ($port != '') {
                $c[ 'port' ] = $port;
            }

            $password = isset($server[ 'password' ]) ? $server[ 'password' ] : '';
            if ($password != '') {
                $c[ 'password' ] = $password;
            }

            $database = isset($server[ 'database' ]) ? $server[ 'database' ] : '';
            if ($database != '') {
                $c[ 'database' ] = $database;
            }

            $timeout = isset($server[ 'timeout' ]) ? $server[ 'timeout' ] : '';
            if ($timeout != '') {
                $c[ 'timeout' ] = $timeout;
            }

            $read_write_timeout = isset($server[ 'read_write_timeout' ]) ? $server[ 'read_write_timeout' ] : '';
            if ($read_write_timeout != '') {
                $c[ 'read_write_timeout' ] = $read_write_timeout;
            }

            $this->instant = new PredisSoftware($c);

            $this->checked_redis = true;

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
     * @return mixed
     */
    public function driver_set($keyword, $value = '', $time = 300, $option = array())
    {
        if ($this->connectServer()) {
            $value = $this->encode($value);
            if (isset($option[ 'skipExisting' ]) && $option[ 'skipExisting' ] == true) {
                return $this->instant->setex($keyword, $time, $value);
            } else {
                return $this->instant->setex($keyword, $time, $value);
            }
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
              'size' => '',
              'data' => $this->instant->info(),
            );

            return $res;
        }

        return array();

    }

    /**
     * @param array $option
     * @return void
     */
    public function driver_clean($option = array())
    {
        if ($this->connectServer()) {
            $this->instant->flushDB();
        }

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