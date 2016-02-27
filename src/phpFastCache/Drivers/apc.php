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
use phpFastCache\Exceptions\phpFastCacheDriverException;

/**
 * Class apc
 * @package phpFastCache\Drivers
 */
class apc extends DriverAbstract
{
    /**
     * phpFastCache_apc constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct($config = array())
    {
        $this->setup($config);

        if (!$this->checkdriver()) {
            throw new phpFastCacheDriverException('APC is not installed, cannot continue.');
        }
    }

    /**
     * @return bool
     */
    public function checkdriver()
    {
        if (extension_loaded('apc') && ini_get('apc.enabled')) {
            return true;
        } else {
            $this->fallback = true;
            return false;
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
        if (isset($option[ 'skipExisting' ]) && $option[ 'skipExisting' ] == true) {
            return apc_add($keyword, $value, $time);
        } else {
            return apc_store($keyword, $value, $time);
        }
    }

    /**
     * @param $keyword
     * @param array $option
     * @return mixed|null
     */
    public function driver_get($keyword, $option = array())
    {
        $data = apc_fetch($keyword, $bo);
        if ($bo === false) {
            return null;
        }
        return $data;

    }

    /**
     * @param $keyword
     * @param array $option
     * @return bool|\string[]
     */
    public function driver_delete($keyword, $option = array())
    {
        return apc_delete($keyword);
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
            $res[ 'data' ] = apc_cache_info('user');
        } catch (\Exception $e) {
            $res[ 'data' ] = array();
        }

        return $res;
    }

    /**
     * @param array $option
     * @return void
     */
    public function driver_clean($option = array())
    {
        @apc_clear_cache();
        @apc_clear_cache('user');
    }

    /**
     * @param $keyword
     * @return bool
     */
    public function driver_isExisting($keyword)
    {
        return (bool) apc_exists($keyword);
    }
}