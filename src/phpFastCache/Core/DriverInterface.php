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

namespace phpFastCache\Core;

/**
 * Interface DriverInterface
 * @package phpFastCache\Core
 */
interface DriverInterface
{
    /**
     * Check if this Cache driver is available for server or not
     * phpFastCache_driver constructor.
     * @param array $config
     */
    public function __construct($config = array());

    /**
     * @return mixed
     */
    public function checkdriver();

    /**
     * Set a obj to cache
     * @param $keyword
     * @param string $value
     * @param int $time
     * @param array $option
     * @return mixed
     */
    public function driver_set(
      $keyword,
      $value = '',
      $time = 300,
      $option = array()
    );

    /**
     * Return null or value of cache
     * @param $keyword
     * @param array $option
     * @return mixed
     */
    public function driver_get($keyword, $option = array());

    /**
     * Show stats of caching
     * Return array("info","size","data")
     * @param array $option
     * @return mixed
     */
    public function driver_stats($option = array());

    /**
     * Delete a cache
     * @param $keyword
     * @param array $option
     * @return mixed
     */
    public function driver_delete($keyword, $option = array());

    /**
     * Clean up whole cache
     * @param array $option
     * @return mixed
     */
    public function driver_clean($option = array());

    /**
     * @param $config_name
     * @param string $value
     */
    public function setup($config_name, $value = '');
}