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
use Memcache as MemcacheSoftware;

/**
 * Class memcache
 * @package phpFastCache\Drivers
 */
class memcache extends DriverAbstract
{

    /**
     * @var \Memcache
     */
    public $instant;

    /**
     * @var int
     */
    protected $memcacheFlags = 0;

    /**
     * phpFastCache_memcache constructor.
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->setup($config);
        if (!$this->checkdriver() && !isset($config[ 'skipError' ])) {
            $this->fallback = true;
        }
        if (class_exists('Memcache')) {
            $this->instant = new MemcacheSoftware();

            if (array_key_exists('compress_data', $config) &&  $config[ 'compress_data' ] === true) {
                $this->memcacheFlags = MEMCACHE_COMPRESSED;
            }
        } else {
            $this->fallback = true;
        }
    }

    /**
     * @return bool
     */
    public function checkdriver()
    {
        // Check memcache
        if (function_exists('memcache_connect')) {
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
        $server = $this->config[ 'memcache' ];
        if (count($server) < 1) {
            $server = array(
              array('127.0.0.1', 11211),
            );
        }

        foreach ($server as $s) {
            $name = $s[ 0 ] . "_" . $s[ 1 ];
            if (!isset($this->checked[ $name ])) {
                try {
                    if (!$this->instant->addserver($s[ 0 ], $s[ 1 ])) {
                        $this->fallback = true;
                    }

                    $this->checked[ $name ] = 1;
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
     * @return array|bool
     */
    public function driver_set(
      $keyword,
      $value = '',
      $time = 300,
      $option = array()
    ) {
        $this->connectServer();

        // Memcache will only allow a expiration timer less than 2592000 seconds,
        // otherwise, it will assume you're giving it a UNIX timestamp.
        if ($time > 2592000) {
            $time = time() + $time;
        }

        if (isset($option[ 'skipExisting' ]) && $option[ 'skipExisting' ] == true) {
            return $this->instant->add($keyword, $value, $this->memcacheFlags, $time);

        } else {
            return $this->instant->set($keyword, $value, $this->memcacheFlags, $time);
        }
    }

    /**
     * @param $keyword
     * @param array $option
     * @return array|null|string
     */
    public function driver_get($keyword, $option = array())
    {
        $this->connectServer();

        // return null if no caching
        // return value if in caching

        $x = $this->instant->get($keyword);

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