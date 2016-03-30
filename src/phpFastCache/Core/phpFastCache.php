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

use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheCoreException;
use phpFastCache\Exceptions\phpFastCacheDriverException;

/**
 * Class phpFastCache
 * @package phpFastCache\Core
 *
 * Handle methods using annotations for IDE
 * because they're handled by __call()
 * Check out DriverInterface to see all
 * the drivers methods magically implemented
 *
 * @method get() driver_get($keyword, $option = array()) Return null or value of cache
 * @method set() driver_set($keyword, $value = '', $time = 300, $option = array()) Set a obj to cache
 * @method delete() delete(string $keyword) Delete key from cache
 * @method clean() clean($option = array()) Clean up whole cache
 * @method checkdriver() checkdriver() Delete key from cache
 * @method stats() stats($option = array()) Show stats of caching
 * @method systemInfo() systemInfo() Return System Information
 *
 */
class phpFastCache
{
    /**
     * @var bool
     */
    public static $disabled = false;

    /**
     * @var array
     */
    public static $config = array(
      'storage' => '', // blank for auto
      'default_chmod' => 0777, // 0777 , 0666, 0644

      'overwrite'  =>  "", // files, sqlite, etc it will overwrite ur storage and all other caching for waiting u fix ur server
      'allow_search'   =>  false, // turn to true will allow $method search("/regex/")

      'fallback' => 'files', //Fall back when old driver is not support

      'securityKey' => 'auto',
      'htaccess' => true,
      'path' => '',

      'memcache' => array(
        array('127.0.0.1', 11211, 1),
          //  array("new.host.ip",11211,1),
      ),

      'redis' => array(
        'host' => '127.0.0.1',
        'port' => '',
        'password' => '',
        'database' => '',
        'timeout' => '',
      ),

      'ssdb' => array(
        'host' => '127.0.0.1',
        'port' => 8888,
        'password' => '',
        'timeout' => '',
      ),

      'extensions' => array(),
      "cache_method"    =>  1, // 1 = normal, 2 = phpfastcache, 3 = memory
      "limited_memory_each_object"  =>  4000, // maximum size (bytes) of object store in memory
    );

    /**
     * @var array
     */
    protected static $tmp = array();

    /**
     * @var DriverAbstract $instance
     */
    public $instance;

    /**
     * phpFastCache constructor.
     * @param string $storage
     * @param array $config
     */
    public function __construct($storage = '', $config = array())
    {
        if (empty($config)) {
            $config = phpFastCache::$config;
        }
        $config[ 'storage' ] = $storage;

        $storage = strtolower($storage);
        if ($storage == '' || $storage == 'auto') {
            $storage = self::getAutoClass($config);
        }

        $this->instance = CacheManager::getInstance($storage, $config);
    }

    /**
     * Cores
     */

    /**
     * @param $config
     * @return string
     * @throws \Exception
     */
    public static function getAutoClass($config)
    {
        $path = self::getPath(false, $config);
        if (is_writable($path)) {
            $driver = 'files';
        } else if (extension_loaded('apc') && ini_get('apc.enabled') && strpos(PHP_SAPI, 'CGI') === false) {
            $driver = 'apc';
        } else if (class_exists('memcached')) {
            $driver = 'memcached';
        } elseif (extension_loaded('wincache') && function_exists('wincache_ucache_set')) {
            $driver = 'wincache';
        } elseif (extension_loaded('xcache') && function_exists('xcache_get')) {
            $driver = 'xcache';
        } else if (function_exists('memcache_connect')) {
            $driver = 'memcache';
        } else if (class_exists('Redis')) {
            $driver = 'redis';
        } else {
            $driver = 'files';
        }

        return $driver;
    }

    /**
     * @param bool $skip_create_path
     * @param $config
     * @return string
     * @throws \Exception
     */
    public static function getPath($skip_create_path = false, $config)
    {
        $tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();

        if (!isset($config[ 'path' ]) || $config[ 'path' ] == '') {
            if (self::isPHPModule()) {
                $path = $tmp_dir;
            } else {
                $document_root_path = rtrim($_SERVER[ 'DOCUMENT_ROOT' ], '/') . '/../';
                $path = isset($_SERVER[ 'DOCUMENT_ROOT' ]) && is_writable($document_root_path)
                    ? $document_root_path
                    : rtrim(__DIR__, '/') . '/';
            }

            if (self::$config[ 'path' ] != '') {
                $path = $config[ 'path' ];
            }

        } else {
            $path = $config[ 'path' ];
        }

        $securityKey = array_key_exists('securityKey',
          $config) ? $config[ 'securityKey' ] : '';
        if ($securityKey == "" || $securityKey == 'auto') {
            $securityKey = self::$config[ 'securityKey' ];
            if ($securityKey == 'auto' || $securityKey == '') {
                $securityKey = isset($_SERVER[ 'HTTP_HOST' ]) ? preg_replace('/^www./',
                  '', strtolower($_SERVER[ 'HTTP_HOST' ])) : "default";
            }
        }
        if ($securityKey != '') {
            $securityKey .= '/';
        }

        $securityKey = self::cleanFileName($securityKey);

        $full_path = rtrim($path,'/') . '/' . $securityKey;
        $full_pathx = md5($full_path);


        if ($skip_create_path == false && !isset(self::$tmp[ $full_pathx ])) {

            if (!@file_exists($full_path) || !@is_writable($full_path)) {
                if (!@file_exists($full_path)) {
                    @mkdir($full_path, self::__setChmodAuto($config));
                }
                if (!@is_writable($full_path)) {
                    @chmod($full_path, self::__setChmodAuto($config));
                }
                if(!@is_writable($full_path)) {
                    // switch back to tmp dir again if the path is not writeable
                    $full_path = rtrim($tmp_dir,'/') . '/' . $securityKey;
                    if (!@file_exists($full_path)) {
                        @mkdir($full_path, self::__setChmodAuto($config));
                    }
                    if (!@is_writable($full_path)) {
                        @chmod($full_path, self::__setChmodAuto($config));
                    }
                }
                if (!@file_exists($full_path) || !@is_writable($full_path)) {
                    throw new phpFastCacheCoreException('PLEASE CREATE OR CHMOD ' . $full_path . ' - 0777 OR ANY WRITABLE PERMISSION!', 92);
                }
            }

            self::$tmp[ $full_pathx ] = true;
            self::htaccessGen($full_path, array_key_exists('htaccess', $config) ? $config[ 'htaccess' ] : false);
        }

        return realpath($full_path);

    }

    /**
     * @param $filename
     * @return mixed
     */
    public static function cleanFileName($filename)
    {
        $regex = array(
          '/[\?\[\]\/\\\=\<\>\:\;\,\'\"\&\$\#\*\(\)\|\~\`\!\{\}]/',
          '/\.$/',
          '/^\./',
        );
        $replace = array('-', '', '');
        return trim(preg_replace($regex, $replace, trim($filename)),'-');
    }

    /**
     * @param $config
     * @return int
     */
    public static function __setChmodAuto($config)
    {
        if (!isset($config[ 'default_chmod' ]) || $config[ 'default_chmod' ] == '' || is_null($config[ 'default_chmod' ])) {
            return 0777;
        } else {
            return $config[ 'default_chmod' ];
        }
    }

    /**
     * @return array
     */
    protected static function getOS()
    {
        $os = array(
          'os' => PHP_OS,
          'php' => PHP_SAPI,
          'system' => php_uname(),
          'unique' => md5(php_uname() . PHP_OS . PHP_SAPI),
        );
        return $os;
    }

    /**
     * @return bool
     */
    public static function isPHPModule()
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
     * @param $path
     * @param bool $create
     * @throws \Exception
     */
    protected static function htaccessGen($path, $create = true)
    {

        if ($create == true) {
            if (!is_writable($path)) {
                try {
                    chmod($path, 0777);
                } catch (phpFastCacheDriverException $e) {
                    throw new phpFastCacheDriverException('PLEASE CHMOD ' . $path . ' - 0777 OR ANY WRITABLE PERMISSION!',
                      92);
                }
            }

            if(!file_exists($path."/.htaccess")) {
                //   echo "write me";
                $html = "order deny, allow \r\n
deny from all \r\n
allow from 127.0.0.1";

                $f = @fopen($path . '/.htaccess', 'w+');
                if (!$f) {
                    throw new phpFastCacheDriverException('PLEASE CHMOD ' . $path . ' - 0777 OR ANY WRITABLE PERMISSION!', 92);
                }
                fwrite($f, $html);
                fclose($f);


            }
        }

    }

    /**
     * @param $name
     * @param string $value
     */
    public static function setup($name, $value = '')
    {
        if (is_array($name)) {
            self::$config = array_merge(self::$config,$name);
        } else {
            self::$config[ $name ] = $value;
        }
    }

    /**
     * @param $something
     */
    public static function debug($something)
    {
        echo "Starting Debugging ...<br>\r\n ";
        if (is_array($something)) {
            echo '<pre>';
            print_r($something);
            echo '</pre>';
            var_dump($something);
        } else {
            echo $something;
        }
        echo "\r\n<br> Ended";
        exit;
    }
}
