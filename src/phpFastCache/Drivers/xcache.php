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
use Exception;

/**
 * Class xcache
 * @package phpFastCache\Drivers
 */
class xcache extends DriverAbstract
{

    /**
     * phpFastCache_xcache constructor.
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
        // Check xcache
        if (extension_loaded('xcache') && function_exists('xcache_get')) {
            return true;
        }
        $this->fallback = true;
        return false;

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

        if (isset($option[ 'skipExisting' ]) && $option[ 'skipExisting' ] == true) {
            if (!$this->isExisting($keyword)) {
                return xcache_set($keyword, serialize($value), $time);
            }
        } else {
            return xcache_set($keyword, serialize($value), $time);
        }
        return false;
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
        $data = unserialize(xcache_get($keyword));
        if ($data === false || $data == '') {
            return null;
        }
        return $data;
    }

    /**
     * @param $keyword
     * @param array $option
     * @return bool
     */
    public function driver_delete($keyword, $option = array())
    {
        return xcache_unset($keyword);
    }

    /**
     * @param array $option
     * @return array
     */
    public function driver_stats($option = array())
    {
        $res = array(
          'info' => '',
          'size' => '',
          'data' => '',
        );

        try {
            $res[ 'data' ] = xcache_list(XC_TYPE_VAR, 100);
        } catch (Exception $e) {
            $res[ 'data' ] = array();
        }
        return $res;
    }

    /**
     * @param array $option
     * @return bool
     */
    public function driver_clean($option = array())
    {
        $cnt = xcache_count(XC_TYPE_VAR);
        for ($i = 0; $i < $cnt; $i++) {
            xcache_clear_cache(XC_TYPE_VAR, $i);
        }
        return true;
    }

    /**
     * @param $keyword
     * @return bool
     */
    public function driver_isExisting($keyword)
    {
        return xcache_isset($keyword);
    }
}