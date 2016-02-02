<?php

/**
 * Interface phpfastcache_driver
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */
interface phpfastcache_driver
{
    /**
     * Check if this Cache driver is available for server or not
     * phpfastcache_driver constructor.
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
      $value = "",
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
}