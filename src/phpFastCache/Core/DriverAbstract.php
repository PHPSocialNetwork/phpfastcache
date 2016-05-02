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

use phpFastCache\Cache\ExtendedCacheItemInterface;
use phpFastCache\Cache\ExtendedCacheItemPoolInterface;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\CacheManager;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class DriverAbstract
 * @package phpFastCache\Core
 */
abstract class DriverAbstract implements CacheItemPoolInterface, ExtendedCacheItemPoolInterface
{
    const DRIVER_CHECK_FAILURE      = '%s is not installed or misconfigured, cannot continue.';
    const DRIVER_DATA_WRAPPER_INDEX = 'd';
    const DRIVER_TIME_WRAPPER_INDEX = 't';

    /**
     * @var array
     */
    public $extension_dir = '_extensions';

    /**
     * @var array
     */
    public $tmp = [];

    /**
     * @var array default options, this will be merge to Driver's Options
     */
    public $config = [];

    /**
     * @var bool
     */
    public $fallback = false;

    /**
     * @var mixed Instance of driver service
     */
    public $instance;


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
     * @param $keyword
     * @param array $option
     * @return null|object
     */
    public function getInfo($keyword, $option = [])
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
     * @param array $option
     * @return mixed
     */
    public function stats(array $option = [])
    {
        return $this->driver_stats($option);
    }

    /**
     * @param array $option
     * @return mixed
     */
    public function clean(array $option = [])
    {
        // handle method
        if ((int)$this->config[ 'cache_method' ] > 1) {
            // use memory
            CacheManager::$memory[ $this->config[ 'instance' ] ] = [];
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
            $return_list = [];
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
     * Extend more time
     * @param $keyword
     * @param int $time
     * @param array $option
     * @return bool
     */
    public function touch($keyword, $time = 300, array $option = [])
    {
        $object = $this->get($keyword, ['all_keys' => true]);
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
     * @param array $list
     * @return array
     */
    public function touchMulti(array $list = [])
    {
        $res = [];
        foreach ($list as $array) {
            $name = $array[ 0 ];
            $res[ $name ] = $this->touch($name, $array[ 1 ],
              isset($array[ 2 ]) ? $array[ 2 ] : []);
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
              isset($v[ 2 ]) ? $v[ 2 ] : []);
        } else {
            throw new phpFastCacheDriverException("Example ->$name = array('VALUE', 300);");
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
        return CacheManager::getInstance(CacheManager::$config[ 'fallback' ]);
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
     * Encode data types such as object/array
     * for driver that does not support
     * non-scalar value
     * @param $data
     * @return string
     */
    protected function encode($data)
    {
        return serialize($data);
    }

    /**
     * Decode data types such as object/array
     * for driver that does not support
     * non-scalar value
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
        if (PHP_SAPI === 'apache2handler') {
            return true;
        } else {
            if (strpos(PHP_SAPI, 'handler') !== false) {
                return true;
            }
        }

        return false;
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
          [
            "path" => $this->config[ 'path' ],
            "cache_method" => 3,
          ]
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
    public function setTags($keyword, $value = '', $time = 0, $tags = [], $option = [])
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        $option[ 'tags' ] = $tags;

        return $this->set($keyword, $value, $time, $option);
    }

    protected function _handleTags($keyword, $time, $tags)
    {
        foreach ($tags as $tag) {
            $list = $this->_tagCaching()->get($this->_getTagName($tag));
            if (is_null($list)) {
                $list = [];
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
    public function getTags($tags = [], $return_content = true, $option = [])
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        $keywords = [];
        $tmp = 0;

        foreach ($tags as $tag) {
            $list = $this->_tagCaching()->get($this->_getTagName($tag));
            $list_return = [];
            if (is_null($list)) {
                $list = [];
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
    public function touchTags($tags = [], $time = 300, $options = [])
    {
        if (!is_array($tags)) {
            $tags = [$tags];
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
    public function deleteTags($tags = [], $option = [])
    {
        if (!is_array($tags)) {
            $tags = [$tags];
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
    public function incrementTags($tags = [], $step = 1, $option = [])
    {
        if (!is_array($tags)) {
            $tags = [$tags];
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
    public function decrementTags($tags = [], $step = 1, $option = [])
    {
        if (!is_array($tags)) {
            $tags = [$tags];
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
     * @param \phpFastCache\Cache\ExtendedCacheItemInterface $item
     * @return array
     */
    public function driverPreWrap(ExtendedCacheItemInterface $item)
    {
        return [
          self::DRIVER_DATA_WRAPPER_INDEX => $item->get(),
          self::DRIVER_TIME_WRAPPER_INDEX => $item->getExpirationDate(),
        ];
    }

    /**
     * @param array $wrapper
     * @return mixed
     */
    public function driverUnwrapData(array $wrapper)
    {
        return $wrapper[ self::DRIVER_DATA_WRAPPER_INDEX ];
    }

    /**
     * @param array $wrapper
     * @return \DateTime
     */
    public function driverUnwrapTime(array $wrapper)
    {
        return $wrapper[ self::DRIVER_TIME_WRAPPER_INDEX ];
    }

    /**
     * V5: Abstract Methods
     */

    /**
     * @param $key
     * @return array [
     *      'd' => 'THE ITEM DATA'
     *      't' => 'THE ITEM DATE EXPIRATION'
     * ]
     *
     */
    abstract public function driverRead($key);

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    abstract public function driverWrite(CacheItemInterface $item);

    /**
     * @return bool
     */
    abstract public function driverClear();

    /**
     * @return bool
     */
    abstract public function driverConnect();

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     */
    abstract public function driverDelete(CacheItemInterface $item);

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     */
    abstract public function driverIsHit(CacheItemInterface $item);
}