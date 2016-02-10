<?php
namespace phpFastCache\Core;

use phpFastCache\Drivers\example;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\CacheManager;

/**
 * Class BasephpFastCache
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 */
abstract class DriverAbstract implements DriverInterface
{

    /**
     * @var array
     */
    public $tmp = array();

    /**
     * @var array default options, this will be merge to Driver's Options
     */
    public $config = array();

    /**
     * @var bool
     */
    public $fallback = false;

    /**
     * @var
     */
    public $instant;

    /**
     * Basic Functions
     * @param $keyword
     * @param string $value
     * @param int $time
     * @param array $option
     * @return bool|null
     */
    public function set($keyword, $value = '', $time = 0, $option = array())
    {
        /**
         * Infinity Time
         * Khoa. B
         */
        if ((int)$time <= 0) {
            // 5 years, however memcached or memory cached will gone when u restart it
            // just recommended for sqlite. files
            $time = 3600 * 24 * 365 * 5;
        }

        /**
         * Temporary disabled phpFastCache::$disabled = true
         * Khoa. B
         */
        if (phpFastCache::$disabled === true) {
            return false;
        }
        $object = array(
          "value" => $value,
          "write_time" => time(),
          "expired_in" => $time,
          "expired_time" => time() + (Int)$time,
        );

        // handle search
        if($this->config['allow_search'] == true) {
            $option['tags'] = array("search");
        }

        // handle tags
        if(isset($option['tags'])) {
            $this->_handleTags($keyword, $time, $option['tags']);
        }

        return $this->driver_set($keyword, $object, $time, $option);

    }

    /**
     * @param $keyword
     * @param array $option
     * @return mixed
     */
    public function get($keyword, $option = array())
    {
       /**
       * Temporary disabled phpFastCache::$disabled = true
       * Khoa. B
       */

        if (phpFastCache::$disabled === true) {
            return null;
        }

        $object = $this->driver_get($keyword, $option);

        if ($object == null) {
            return null;
        }

        $value = isset($object[ 'value' ]) ? $object[ 'value' ] : null;
        return isset($option[ 'all_keys' ]) && $option[ 'all_keys' ] ? $object : $value;
    }

    /**
     * @param $keyword
     * @param array $option
     * @return null|object
     */
    public function getInfo($keyword, $option = array())
    {
        $object = $this->driver_get($keyword, $option);

        if ($object == null) {
            return null;
        }
        return $object;
    }

    /**
     * @param $keyword
     * @param array $option
     * @return mixed
     */
    public function delete($keyword, $option = array())
    {
        return $this->driver_delete($keyword, $option);
    }

    /**
     * @param array $option
     * @return mixed
     */
    public function stats($option = array())
    {
        return $this->driver_stats($option);
    }

    /**
     * @param array $option
     * @return mixed
     */
    public function clean($option = array())
    {
        return $this->driver_clean($option);
    }

    /**
     * @param $keyword
     * @return bool
     */
    public function isExisting($keyword)
    {
        if (method_exists($this, 'driver_isExisting')) {
            return $this->driver_isExisting($keyword);
        }

        $data = $this->get($keyword);
        if ($data == null) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * Searches though the cache for keys that match the given query.
     * @param $query_as_regex_or_string
     * @param bool $search_in_value
     * @return mixed
     * @throws phpFastCacheDriverException
     */
    public function search($query_as_regex_or_string, $search_in_value = false)
    {
       if($this->config['allow_search'] != true) {
           throw new phpFastCacheDriverException('Please setup allow_search = true');
       } else {
            $list = $this->getTags("search",$search_in_value);
            $tmp = explode("/",$query_as_regex_or_string,2);
            $regex = isset($tmp[1]) ? true : false;
            $return_list = array();
            foreach($list as $tag) {
                foreach($tag as $keyword => $value) {
                    $gotcha = false;
                    if($search_in_value == true) {
                        $value = $this->get($keyword);
                    }

                    if($regex == true && $gotcha == false)
                    {     // look in keyword
                        if(preg_match($query_as_regex_or_string,$keyword))
                        {
                            $return_list[$keyword] = $value;
                            $gotcha = true;
                        }
                    }
                    if($gotcha == false ) {
                        if(strpos($keyword, $query_as_regex_or_string) !== false)
                        {
                            $return_list[$keyword] = $value;
                            $gotcha = true;
                        }
                    }

                    if($search_in_value == true && $gotcha == false)
                    { // value search
                        if($regex == true &&   $gotcha == false )
                        {
                            if (preg_match($query_as_regex_or_string, $value))
                            {
                                $return_list[$keyword] = $value;
                                $gotcha = true;
                            }
                        }
                        if($gotcha == false) {
                            if (strpos($value, $query_as_regex_or_string) !== false)
                            {
                                $return_list[$keyword] = $value;
                                $gotcha = true;
                            }
                        }
                    }
                } // each tags loop
            } // end foreach
            return $return_list;
       }
       return array();
    }

    /**
     * @param $keyword
     * @param int $step
     * @param array $option
     * @return bool
     */
    public function increment($keyword, $step = 1, $option = array())
    {
        $object = $this->get($keyword, array('all_keys' => true));
        if ($object == null) {
            return false;
        } else {
            $value = (Int)$object[ 'value' ] + (Int)$step;
            $time = $object[ 'expired_time' ] - time();
            $this->set($keyword, $value, $time, $option);
            return true;
        }
    }

    /**
     * @param $keyword
     * @param int $step
     * @param array $option
     * @return bool
     */
    public function decrement($keyword, $step = 1, $option = array())
    {
        $object = $this->get($keyword, array('all_keys' => true));
        if ($object == null) {
            return false;
        } else {
            $value = (Int)$object[ 'value' ] - (Int)$step;
            $time = $object[ 'expired_time' ] - time();
            $this->set($keyword, $value, $time, $option);
            return true;
        }
    }

    /**
     * Extend more time
     * @param $keyword
     * @param int $time
     * @param array $option
     * @return bool
     */
    public function touch($keyword, $time = 300, $option = array())
    {
        $object = $this->get($keyword, array('all_keys' => true));
        if ($object == null) {
            return false;
        } else {
            $value = $object[ 'value' ];
            $time = $object[ 'expired_time' ] - time() + $time;
            $this->set($keyword, $value, $time, $option);
            return true;
        }
    }


    /**
     * Other Functions Built-int for phpFastCache since 1.3
     */

    /**
     * @param array $list
     */
    public function setMulti($list = array())
    {
        foreach ($list as $array) {
            $this->set($array[ 0 ], isset($array[ 1 ]) ? $array[ 1 ] : 0,
              isset($array[ 2 ]) ? $array[ 2 ] : array());
        }
    }

    /**
     * @param array $list
     * @return array
     */
    public function getMulti($list = array())
    {
        $res = array();
        foreach ($list as $array) {
            $name = $array[ 0 ];
            $res[ $name ] = $this->get($name,
              isset($array[ 1 ]) ? $array[ 1 ] : array());
        }
        return $res;
    }

    /**
     * @param array $list
     * @return array
     */
    public function getInfoMulti($list = array())
    {
        $res = array();
        foreach ($list as $array) {
            $name = $array[ 0 ];
            $res[ $name ] = $this->getInfo($name,
              isset($array[ 1 ]) ? $array[ 1 ] : array());
        }
        return $res;
    }

    /**
     * @param array $list
     * @param array $option
     */
    public function deleteMulti($list = array(), $option = array())
    {
        foreach ($list as $item) {
            if (is_array($item) && count($item) === 2) {
                $this->delete($item[ 0 ], $item[ 1 ]);
            }
        }
    }

    /**
     * @param array $list
     * @return array
     */
    public function isExistingMulti($list = array())
    {
        $res = array();
        foreach ($list as $array) {
            $name = $array[ 0 ];
            $res[ $name ] = $this->isExisting($name);
        }
        return $res;
    }

    /**
     * @param array $list
     * @return array
     */
    public function incrementMulti($list = array())
    {
        $res = array();
        foreach ($list as $array) {
            $name = $array[ 0 ];
            $res[ $name ] = $this->increment($name, $array[ 1 ],
              isset($array[ 2 ]) ? $array[ 2 ] : array());
        }
        return $res;
    }

    /**
     * @param array $list
     * @return array
     */
    public function decrementMulti($list = array())
    {
        $res = array();
        foreach ($list as $array) {
            $name = $array[ 0 ];
            $res[ $name ] = $this->decrement($name, $array[ 1 ],
              isset($array[ 2 ]) ? $array[ 2 ] : array());
        }
        return $res;
    }

    /**
     * @param array $list
     * @return array
     */
    public function touchMulti($list = array())
    {
        $res = array();
        foreach ($list as $array) {
            $name = $array[ 0 ];
            $res[ $name ] = $this->touch($name, $array[ 1 ],
              isset($array[ 2 ]) ? $array[ 2 ] : array());
        }
        return $res;
    }

    /**
     * @param $config_name
     * @param string $value
     */
    public function setup($config_name, $value = '')
    {
        /*
         * Config for class
         */
        if (is_array($config_name)) {
            $this->config = array_merge($this->config, $config_name);
        } else {
            $this->config[ $config_name ] = $value;
        }
    }

    /**
     * @param int $time
     */
    public function autoCleanExpired($time = 3600)
    {
    }

    /**
     * Magic methods
     */

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param $name
     * @param $v
     * @return bool|null
     * @throws \Exception
     */
    public function __set($name, $v)
    {
        if (isset($v[ 1 ]) && is_scalar($v[ 1 ])) {
            return $this->set($name, $v[ 0 ], $v[ 1 ],
              isset($v[ 2 ]) ? $v[ 2 ] : array());
        } else {
            throw new phpFastCacheDriverException("Example ->$name = array('VALUE', 300);", 98);
        }
    }

    /**
     * @param $name
     * @param $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->instant, $name), $args);
    }


    /**
     * Base Methods
     */


    /**
     * @return mixed
     */
    protected function backup()
    {
        return phpFastCache(phpFastCache::$config[ 'fallback' ]);
    }

    /**
     * @param $name
     */
    protected function required_extension($name)
    {
        require_once(__DIR__ . '/../_extensions/' . $name);
    }


    /**
     * @param $file
     * @return string
     * @throws \Exception
     */
    protected function readfile($file)
    {
        if (function_exists('file_get_contents')) {
            return file_get_contents($file);
        } else {
            $string = '';

            $file_handle = @fopen($file, 'r');
            if (!$file_handle) {
                throw new phpFastCacheDriverException("Can't Read File", 96);

            }
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                $string .= $line;
            }
            fclose($file_handle);

            return $string;
        }
    }

    /**
     * return PATH for Files & PDO only
     * @param bool $create_path
     * @return string
     * @throws \Exception
     */
    public function getPath($create_path = false)
    {
        return phpFastCache::getPath($create_path, $this->config);
    }


    /**
     *  Object for Files & SQLite
     * @param $data
     * @return string
     */
    protected function encode($data)
    {
        return serialize($data);
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function decode($value)
    {
        $x = @unserialize($value);
        if ($x == false) {
            return $value;
        } else {
            return $x;
        }
    }

    /**
     * Check phpModules or CGI
     * @return bool
     */
    protected function isPHPModule()
    {
        return phpFastCache::isPHPModule();
    }


    /**
     * @param $class
     * @return bool
     */
    protected function isExistingDriver($class)
    {
        if (file_exists(__DIR__ . '/Drivers/' . $class . '.php')) {
            require_once(__DIR__ . '/Drivers/' . $class . '.php');
            if (class_exists("phpFastCache_" . $class)) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return int
     */
    protected function __setChmodAuto()
    {
        return phpFastCache::__setChmodAuto($this->config);
    }


    /* FOR PLUGINS TAGS */

    protected function _getTagName($tag) {
        return "__tag__".$tag;
    }

    protected function _tagCaching() {
        return CacheManager::Sqlite(array("path"    => $this->config['path']));
    }

    /**
     * @param string $keyword
     * @param mixed $value
     * @param integer $time
     * @param array $tags
     * @param array $option | $option = array("tags" => array("a","b","c")
     * @return mixed
     */
    public function setTags($keyword, $value = '', $time = 0, $tags = array(), $option = array()) {
        if(!is_array($tags)) {
            $tags = array($tags);
        }
        $option['tags'] = $tags;
        return $this->set($keyword,$value,$time, $option);
    }

    protected function _handleTags($keyword, $time, $tags) {
        foreach($tags as $tag) {
            $list = $this->_tagCaching()->get($this->_getTagName($tag));
            if(is_null($list)) {
                $list = array();
            }
            $list[$keyword] = time() + $time;
            $this->_tagCaching()->set($this->_getTagName($tag),$list,3600*24*30);
        }
    }


    /**
     * @param array $tags
     * @param bool $return_content
     * @param array $option | $option = array("tags" => array("a","b","c")
     * @return array
     */
    public function getTags($tags = array(), $return_content = true, $option = array()) {
        if(!is_array($tags)) {
            $tags = array($tags);
        }
        $keywords = array();
        $tmp = 0;

        foreach($tags as $tag) {
            $list = $this->_tagCaching()->get($this->_getTagName($tag));
            $list_return = array();
            if(is_null($list)) {
                $list = array();
            }
            foreach($list as $keyword=>$time) {
                if($time <= time()) {
                    unset($list[$keyword]);
                } else {
                    if($tmp < $time) {
                        $tmp = $time;
                    }
                    if($return_content == true) {
                        $list_return[$keyword] = $this->get($keyword);
                    } else {
                        $list_return[$keyword] = $time;
                    }
                }
            }

            $this->_tagCaching()->set($this->_getTagName($tag),$list,$tmp);
            $keywords[$tag] = $list_return;
        }
        return $keywords;
    }

    /**
     * @param array $tags | array("a","b","c")
     * @param int $time
     * @param array $options
     * @return mixed
     * @internal param array $option | $option = array("tags" => array("a","b","c")
     */
    public function touchTags($tags = array(), $time = 300,  $options = array()) {
        if(!is_array($tags)) {
            $tags = array($tags);
        }
        $lists = $this->getTags($tags);
        foreach($lists as $tag=>$keywords) {
            foreach($keywords as $keyword=>$time) {
                $this->touch($keyword, $time, $options);
            }
        }
        return true;
    }

    /**
     * @param array $tags | array("a","b","c")
     * @param array $option | $option = array("tags" => array("a","b","c")
     * @return mixed
     */
    public function deleteTags($tags = array(), $option = array()) {
        if(!is_array($tags)) {
            $tags = array($tags);
        }
        $lists = $this->getTags($tags);
        foreach($lists as $tag=>$keywords) {
            foreach($keywords as $keyword=>$time) {
                $this->delete($keyword, $option);
            }
        }
        return true;
    }


    /**
     * @param array $tags | array("a","b","c")
     * @param integer
     * @param array $option | $option = array("tags" => array("a","b","c")
     * @return mixed
     */
    public function incrementTags($tags = array(), $step = 1, $option = array()) {
        if(!is_array($tags)) {
            $tags = array($tags);
        }
        $lists = $this->getTags($tags);
        foreach($lists as $tag=>$keywords) {
            foreach($keywords as $keyword=>$time) {
                $this->increment($keyword, $step, $option);
            }
        }
        return true;
    }

    /**
     * @param array $tags | array("a","b","c")
     * @param integer
     * @param array $option | $option = array("tags" => array("a","b","c")
     * @return mixed
     */
    public function decrementTags($tags = array(), $step = 1, $option = array()) {
        if(!is_array($tags)) {
            $tags = array($tags);
        }
        $lists = $this->getTags($tags);
        foreach($lists as $tag=>$keywords) {
            foreach($keywords as $keyword=>$time) {
                $this->decrement($keyword, $step, $option);
            }
        }
        return true;
    }

    /**
     * @param $value
     */
    protected  function _kbdebug($value) {
        echo "<pre>";
        print_r($value);
        echo "</pre>";
    }

}