<?php

/**
 * Class phpfastcache_example
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */
class phpfastcache_example extends BasePhpFastCache implements phpfastcache_driver
{
    /**
     * phpfastcache_example constructor.
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->setup($config);
        if (!$this->checkdriver() && !isset($config[ 'skipError' ])) {
            throw new Exception("Can't use this driver for your website!");
        }

    }

    /**
     * @return bool
     */
    public function checkdriver()
    {
        // return true;
        return false;
    }

    /**
     *
     */
    public function connectServer()
    {

    }

    /**
     * @param $keyword
     * @param string $value
     * @param int $time
     * @param array $option
     */
    public function driver_set($keyword, $value = "", $time = 300, $option = array())
    {
        if (isset($option[ 'skipExisting' ]) && $option[ 'skipExisting' ] == true) {
            // skip driver
        } else {
            // add driver
        }

    }

    /**
     * @param $keyword
     * @param array $option
     * @return null
     */
    public function driver_get($keyword, $option = array())
    {
        // return null if no caching
        // return value if in caching

        return null;
    }

    /**
     * @param $keyword
     * @param array $option
     */
    public function driver_delete($keyword, $option = array())
    {

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

        return $res;
    }

    /**
     * @param array $option
     */
    public function driver_clean($option = array())
    {

    }

    /**
     * @param $keyword
     */
    public function driver_isExisting($keyword)
    {

    }
}