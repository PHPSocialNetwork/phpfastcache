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
use Memcached as MemcachedSoftware;

/**
 * Class memcached
 * @package phpFastCache\Drivers
 */
class memcached extends DriverAbstract
{

    /**
     * @var \Memcached
     */
    public $instant;

    /**
     * phpFastCache_memcached constructor.
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->setup($config);

        if (!$this->checkdriver() && !isset($config[ 'skipError' ])) {
            $this->fallback = true;
        }

        if (class_exists('Memcached')) {
            $this->instant = new MemcachedSoftware();
        } else {
            $this->fallback = true;
        }

    }

    /**
     * @return bool
     */
    public function checkdriver()
    {
        if (class_exists('Memcached')) {
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
        if ($this->checkdriver() == false) {
            return false;
        }

        $s = $this->config[ 'memcache' ];
        if (count($s) < 1) {
            $s = array(
              array('127.0.0.1', 11211, 100),
            );
        }

        foreach ($s as $server) {
            $name = isset($server[ 0 ]) ? $server[ 0 ] : '127.0.0.1';
            $port = isset($server[ 1 ]) ? $server[ 1 ] : 11211;
            $sharing = isset($server[ 2 ]) ? $server[ 2 ] : 0;
            $checked = $name . '_' . $port;
            if (!isset($this->checked[ $checked ])) {
                try {
                    if ($sharing > 0) {
                        if (!$this->instant->addServer($name, $port,
                          $sharing)
                        ) {
                            $this->fallback = true;
                        }
                    } else {

                        if (!$this->instant->addServer($name, $port)) {
                            $this->fallback = true;
                        }
                    }
                    $this->checked[ $checked ] = 1;
                } catch (\Exception $e) {
                    $this->fallback = true;
                }

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

        // Memcache will only allow a expiration timer less than 2592000 seconds,
        // otherwise, it will assume you're giving it a UNIX timestamp.
        if ($time > 2592000) {
            $time = time() + $time;
        }

        if (isset($option[ 'isExisting' ]) && $option[ 'isExisting' ] == true) {
            return $this->instant->add($keyword, $value, $time);
        } else {
            return $this->instant->set($keyword, $value, $time);

        }
    }

    /**
     * @param $keyword
     * @param array $option
     * @return mixed|null
     */
    public function driver_get($keyword, $option = array())
    {
        // return null if no caching
        // return value if in caching
        $this->connectServer();
        $x = @$this->instant->get($keyword);// Prevent memcached to return a warning for long keywords
        if ($x == false) {
            return null;
        } else {
            return $x;
        }
    }

    /**
     * @param $keyword
     * @param array $option
     * @return void
     */
    public function driver_delete($keyword, $option = array())
    {
        $this->connectServer();
        $this->instant->delete($keyword);
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
          'data' => $this->instant->getStats(),
        );

        return $res;
    }

    /**
     * @param array $option
     * @return void
     */
    public function driver_clean($option = array())
    {
        $this->connectServer();
        $this->instant->flush();
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