<?php

/**
 * Class phpfastcache_apc
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */
class phpfastcache_apc extends BasePhpFastCache implements phpfastcache_driver
{
    /**
     * phpfastcache_apc constructor.
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
        // Check apc
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
      $value = "",
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
        // return null if no caching
        // return value if in caching

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
          "info" => "",
          "size" => "",
          "data" => "",
        );

        try {
            $res[ 'data' ] = apc_cache_info("user");
        } catch (Exception $e) {
            $res[ 'data' ] = array();
        }

        return $res;
    }

    /**
     * @param array $option
     */
    public function driver_clean($option = array())
    {
        @apc_clear_cache();
        @apc_clear_cache("user");
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