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

namespace phpFastCache;

use phpFastCache\Cache\ExtendedCacheItemPoolInterface;
use phpFastCache\Core\phpFastCache;
use phpFastCache\Core\DriverAbstract;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class CacheManager
 * @package phpFastCache
 *
 * @method static DriverAbstract Apc() Apc($config = []) Return a driver "apc" instance
 * @method static DriverAbstract Cookie() Cookie($config = []) Return a driver "cookie" instance
 * @method static DriverAbstract Files() Files($config = []) Return a driver "files" instance
 * @method static DriverAbstract Memcache() Memcache($config = []) Return a driver "memcache" instance
 * @method static DriverAbstract Memcached() Memcached($config = []) Return a driver "memcached" instance
 * @method static DriverAbstract Predis() Predis($config = []) Return a driver "predis" instance
 * @method static DriverAbstract Redis() Redis($config = []) Return a driver "redis" instance
 * @method static DriverAbstract Sqlite() Sqlite($config = []) Return a driver "sqlite" instance
 * @method static DriverAbstract Ssdb() Ssdb($config = []) Return a driver "ssdb" instance
 * @method static DriverAbstract Wincache() Wincache($config = []) Return a driver "wincache" instance
 * @method static DriverAbstract Xcache() Xcache($config = []) Return a driver "xcache" instance
 *
 */
class CacheManager
{
    /**
     * @var bool
     */
    public static $disabled = false;

    /**
     * @var array
     */
    public static $config = [
      'default_chmod' => 0777, // 0777 , 0666, 0644

      'overwrite' => "", // files, sqlite, etc it will overwrite ur storage and all other caching for waiting u fix ur server
      'allow_search' => false, // turn to true will allow $method search("/regex/")

      'fallback' => 'files', //Fall back when old driver is not support

      'securityKey' => 'auto',
      'htaccess' => true,
      'path' => '',


      'extensions' => [],
      "cache_method" => 2, // 1 = normal, 2 = phpfastcache, 3 = memory
      "limited_memory_each_object" => 4000, // maximum size (bytes) of object store in memory
    ];

    /**
     * @var array
     */
    protected static $tmp = [];

    protected static $namespacePath;
    protected static $instances = [];

    /**
     * @param string $driver
     * @param array $config
     * @return ExtendedCacheItemPoolInterface
     */
    public static function getInstance($driver = 'auto', $config = [])
    {
        $driver = ucfirst(strtolower($driver));
        $config = array_merge(self::$config, $config);
        if ($driver === 'auto') {
            $driver = self::getAutoClass($config);
        }

        $instance = crc32($driver . serialize($config));
        if (!isset(self::$instances[ $instance ])) {
            $class = self::getNamespacePath() . $driver . '\Driver';
            self::$instances[ $instance ] = new $class($config);
        } else {
            trigger_error('Calling CacheManager::getInstance for already instanced drivers is a bad practice and have a significant impact on performances.
            See https://github.com/PHPSocialNetwork/phpfastcache/wiki/[V5]-Why-calling-getInstance%28%29-each-time-is-a-bad-practice-%3F');
        }

        return self::$instances[ $instance ];
    }

    /**
     * @param $config
     * @return string
     * @throws \Exception
     */
    public static function getAutoClass($config)
    {
        static $autoDriver;
        $systemDrivers = ['Sqlite', 'Files', 'Apc', 'Memcache', 'Memcached', 'Predis', 'Redis', 'Ssdb', 'Wincache', 'Xcache'];

        if ($autoDriver === null) {
            foreach ($systemDrivers as $driver) {
                try {
                    self::getInstance($driver, $config);
                    $autoDriver = $driver;
                } catch (phpFastCacheDriverCheckException $e) {
                    continue;
                }
            }
        }

        return $autoDriver;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return \Psr\Cache\CacheItemPoolInterface
     */
    public static function __callStatic($name, $arguments)
    {
        $options = (array_key_exists(0, $arguments) && is_array($arguments) ? $arguments[ 0 ] : []);

        return self::getInstance($name, $options);
    }

    /**
     * @return bool
     */
    public static function clearInstances()
    {
        foreach (self::$instances as &$instance) {
            unset($instance);
        }

        return !count(self::$instances);
    }

    /**
     * @return string
     */
    public static function getNamespacePath()
    {
        return self::$namespacePath ?: __NAMESPACE__ . '\Drivers\\';
    }

    /**
     * @param string $path
     */
    public static function setNamespacePath($path)
    {
        self::$namespacePath = $path;
    }

    /**
     * @param $name
     * @param string $value
     */
    public static function setup($name, $value = '')
    {
        if (is_array($name)) {
            self::$config = array_merge(self::$config, $name);
        } else {
            self::$config[ $name ] = $value;
        }
    }
}
