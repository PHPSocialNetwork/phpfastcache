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

use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\CacheManager;

/**
 * Class DriverAbstract
 * @package phpFastCache\Core
 */
abstract class DriverAbstract implements DriverInterface
{

    /**
     * @var array
     */
    public $extension_dir = '_extensions';

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


    public function __destruct()
    {
        // clean up the memory and don't want for PHP clean for caching method "phpfastcache"
        if (isset($this->config[ 'instance' ]) && (int)$this->config[ 'cache_method' ] === 3) {
            CacheManager::cleanCachingMethod($this->config[ 'instance' ]);
        }
    }

    /**
     * @param $keyword
     * @return string
     */
    protected function encodeFilename($keyword)
    {
        // return trim(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $keyword), '_'));
        // return rtrim(base64_encode($keyword), '=');
        return md5($keyword);
    }

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
            /**
             * 5 years, however memcached or memory cached will gone when u restart it
             * just recommended for sqlite. files
             */
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
          "expired_time" => time() + (int)$time,
          "size" => (is_array($value) || is_object($value)) ? strlen(serialize($value)) : strlen((String)$value),
        );

        // handle search
        if (isset($this->config[ 'allow_search' ]) && $this->config[ 'allow_search' ] == true) {
            $option[ 'tags' ][] = "search";
        }

        // handle tags
        if (isset($option[ 'tags' ])) {
            $this->_handleTags($keyword, $time, $option[ 'tags' ]);
        }

        // handle method
        if ((int)$this->config[ 'cache_method' ] > 1 && isset($object[ 'size' ]) && (int)$object[ 'size' ] <= (int)$this->config[ 'limited_memory_each_object' ]) {
            CacheManager::$memory[ $this->config[ 'instance' ] ][ $keyword ] = $object;
            if (in_array((int)$this->config[ 'cache_method' ], array(3, 4))) {
                return true;
            }
        }
        $this->_hit("set", 1);
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

        // handle method
        if ((int)$this->config[ 'cache_method' ] > 1) {
            if (isset(CacheManager::$memory[ $this->config[ 'instance' ] ][ $keyword ])) {
                $object = CacheManager::$memory[ $this->config[ 'instance' ] ][ $keyword ];
            }
        }

        if (!isset($object)) {
            $this->_hit("get", 1);
            $object = $this->driver_get($keyword, $option);

            // handle method
            if ((int)$this->config[ 'cache_method' ] > 1 && isset($object[ 'size' ]) && (int)$object[ 'size' ] <= (int)$this->config[ 'limited_memory_each_object' ]) {
                CacheManager::$memory[ $this->config[ 'instance' ] ][ $keyword ] = $object;
            }
            // end handle method
        }

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
        if ((int)$this->config[ 'cache_method' ] > 1) {
            if (isset(CacheManager::$memory[ $this->config[ 'instance' ] ][ $keyword ])) {
                $object = CacheManager::$memory[ $this->config[ 'instance' ] ][ $keyword ];
            }
        }
        if (!isset($object)) {
            $object = $this->driver_get($keyword, $option);
        }
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
    public function delete($keyword, array $option = array())
    {
        // handle method
        if ((int)$this->config[ 'cache_method' ] > 1) {
            // use memory
            unset(CacheManager::$memory[ $this->config[ 'instance' ] ][ $keyword ]);
        }
        // end handle method
        return $this->driver_delete($keyword, $option);
    }

    /**
     * @param array $option
     * @return mixed
     */
    public function stats(array $option = array())
    {
        return $this->driver_stats($option);
    }

    /**
     * @param array $option
     * @return mixed
     */
    public function clean(array $option = array())
    {
        // handle method
        if ((int)$this->config[ 'cache_method' ] > 1) {
            // use memory
            CacheManager::$memory[ $this->config[ 'instance' ] ] = array();
        }
        // end handle method
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
        if ($this->config[ 'allow_search' ] != true) {
            throw new phpFastCacheDriverException('Please setup allow_search = true');
        } else {
            $list = $this->getTags("search", $search_in_value);
            $tmp = explode("/", $query_as_regex_or_string, 2);
            $regex = isset($tmp[ 1 ]) ? true : false;
            $return_list = array();
            foreach ($list as $tag) {
                foreach ($tag as $keyword => $value) {
                    $gotcha = false;
                    if ($search_in_value == true) {
                        $value = $this->get($keyword);
                    }

                    if ($regex == true && $gotcha == false) {     // look in keyword
                        if (preg_match($query_as_regex_or_string, $keyword)) {
                            $return_list[ $keyword ] = $value;
                            $gotcha = true;
                        }
                    }
                    if ($gotcha == false) {
                        if (strpos($keyword, $query_as_regex_or_string) !== false) {
                            $return_list[ $keyword ] = $value;
                            $gotcha = true;
                        }
                    }

                    if ($search_in_value == true && $gotcha == false) { // value search
                        if ($regex == true && $gotcha == false) {
                            if (preg_match($query_as_regex_or_string, $value)) {
                                $return_list[ $keyword ] = $value;
                                $gotcha = true;
                            }
                        }
                        if ($gotcha == false) {
                            if (strpos($value, $query_as_regex_or_string) !== false) {
                                $return_list[ $keyword ] = $value;
                                $gotcha = true;
                            }
                        }
                    }
                } // each tags loop
            } // end foreach
            return $return_list;
        }
    }

    /**
     * @param $keyword
     * @param int $step
     * @param array $option
     * @return bool
     */
    public function increment($keyword, $step = 1, array $option = array())
    {
        $object = $this->get($keyword, array('all_keys' => true));
        if ($object == null) {
            return false;
        } else {
            $value = (int)$object[ 'value' ] + (int)$step;
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
    public function decrement($keyword, $step = 1, array $option = array())
    {
        $object = $this->get($keyword, array('all_keys' => true));
        if ($object == null) {
            return false;
        } else {
            $value = (int)$object[ 'value' ] - (int)$step;
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
    public function touch($keyword, $time = 300, array $option = array())
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
    public function setMulti(array $list = array())
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
    public function getMulti(array $list = array())
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
    public function getInfoMulti(array $list = array())
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
    public function deleteMulti(array $list = array(), array $option = array())
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
    public function isExistingMulti(array $list = array())
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
    public function incrementMulti(array $list = array())
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
    public function decrementMulti(array $list = array())
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
    public function touchMulti(array $list = array())
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
     * @return void
     */
    protected function required_extension($name)
    {
        require_once(__DIR__ . '/../' . $this->extension_dir . '/' . $name . '.' . PHP_EXT);
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
        return class_exists("\\phpFastCache\\Drivers\\{$class}");
    }


    /**
     * @return int
     */
    protected function __setChmodAuto()
    {
        return phpFastCache::__setChmodAuto($this->config);
    }


    /**
     * @param $tag
     * @return string
     */
    protected function _getTagName($tag)
    {
        return "__tag__" . $tag;
    }

    /**
     * @return \phpFastCache\Core\DriverAbstract
     */
    protected function _tagCaching()
    {
        return CacheManager::Sqlite(
          array(
            "path" => $this->config[ 'path' ],
            "cache_method" => 3,
          )
        );
    }

    /**
     * @param string $keyword
     * @param mixed $value
     * @param integer $time
     * @param array $tags
     * @param array $option | $option = array("tags" => array("a","b","c")
     * @return mixed
     */
    public function setTags($keyword, $value = '', $time = 0, $tags = array(), $option = array())
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }
        $option[ 'tags' ] = $tags;
        return $this->set($keyword, $value, $time, $option);
    }

    protected function _handleTags($keyword, $time, $tags)
    {
        foreach ($tags as $tag) {
            $list = $this->_tagCaching()->get($this->_getTagName($tag));
            if (is_null($list)) {
                $list = array();
            }
            $list[ $keyword ] = time() + $time;
            $this->_tagCaching()->set($this->_getTagName($tag), $list, 3600 * 24 * 30);
        }
    }


    /**
     * @param array $tags
     * @param bool $return_content
     * @param array $option | $option = array("tags" => array("a","b","c")
     * @return array
     */
    public function getTags($tags = array(), $return_content = true, $option = array())
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }
        $keywords = array();
        $tmp = 0;

        foreach ($tags as $tag) {
            $list = $this->_tagCaching()->get($this->_getTagName($tag));
            $list_return = array();
            if (is_null($list)) {
                $list = array();
            }
            foreach ($list as $keyword => $time) {
                if ($time <= time()) {
                    unset($list[ $keyword ]);
                } else {
                    if ($tmp < $time) {
                        $tmp = $time;
                    }
                    if ($return_content == true) {
                        $list_return[ $keyword ] = $this->get($keyword);
                    } else {
                        $list_return[ $keyword ] = $time;
                    }
                }
            }

            $this->_tagCaching()->set($this->_getTagName($tag), $list, $tmp);
            $keywords[ $tag ] = $list_return;
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
    public function touchTags($tags = array(), $time = 300, $options = array())
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }
        $lists = $this->getTags($tags);
        foreach ($lists as $tag => $keywords) {
            foreach ($keywords as $keyword => $time) {
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
    public function deleteTags($tags = array(), $option = array())
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }
        $lists = $this->getTags($tags);
        foreach ($lists as $tag => $keywords) {
            foreach ($keywords as $keyword => $time) {
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
    public function incrementTags($tags = array(), $step = 1, $option = array())
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }
        $lists = $this->getTags($tags);
        foreach ($lists as $tag => $keywords) {
            foreach ($keywords as $keyword => $time) {
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
    public function decrementTags($tags = array(), $step = 1, $option = array())
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }
        $lists = $this->getTags($tags);
        foreach ($lists as $tag => $keywords) {
            foreach ($keywords as $keyword => $time) {
                $this->decrement($keyword, $step, $option);
            }
        }
        return true;
    }

    /**
     * @param $value
     */
    protected function _kbdebug($value)
    {
        /*
        echo "<pre>";
        print_r($value);
        echo "</pre>";
        */
    }

    public function _hit($index, $step = 1)
    {
        $instance = $this->config[ 'instance' ];
        $current = isset(CacheManager::$hit[ $instance ][ 'data' ][ $index ]) ? CacheManager::$hit[ $instance ][ 'data' ][ $index ] : 0;
        CacheManager::$hit[ $instance ][ 'data' ][ $index ] = $current + ($step);
    }

}