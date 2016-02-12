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
 * Class wincache
 * @package phpFastCache\Drivers
 */
class wincache extends DriverAbstract
{

    /**
     * phpFastCache_wincache constructor.
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
        if (extension_loaded('wincache') && function_exists('wincache_ucache_set')) {
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
            return wincache_ucache_add($keyword, $value, $time);
        } else {
            return wincache_ucache_set($keyword, $value, $time);
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

        $x = wincache_ucache_get($keyword, $suc);

        if ($suc == false) {
            return null;
        } else {
            return $x;
        }
    }

    /**
     * @param $keyword
     * @param array $option
     * @return bool
     */
    public function driver_delete($keyword, $option = array())
    {
        return wincache_ucache_delete($keyword);
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
          'data' => wincache_scache_info(),
        );
        return $res;
    }

    /**
     * @param array $option
     * @return bool
     */
    public function driver_clean($option = array())
    {
        wincache_ucache_clear();
        return true;
    }

    /**
     * @param $keyword
     * @return bool
     */
    public function driver_isExisting($keyword)
    {
        if (wincache_ucache_exists($keyword)) {
            return true;
        } else {
            return false;
        }
    }
}