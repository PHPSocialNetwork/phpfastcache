<?php

/**
 * Class phpfastcache_wincache
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */
class phpfastcache_wincache extends BasePhpFastCache implements phpfastcache_driver
{

    /**
     * phpfastcache_wincache constructor.
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
        if (extension_loaded('wincache') && function_exists("wincache_ucache_set")) {
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
          "info" => "",
          "size" => "",
          "data" => wincache_scache_info(),
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