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
use phpFastCache\Core\DriverAbstract;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use CouchbaseCluster;

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
     * @var int
     */
    public static $ReadHits = 0;

    /**
     * @var int
     */
    public static $WriteHits = 0;

    /**
     * @var array
     */
    protected static $config = [
      'default_chmod' => 0777, // 0777 recommended
      'fallback' => false, //Fall back when old driver is not support
      'securityKey' => 'auto',// The securityKey that will be used to create sub-directory
      'htaccess' => true,// Auto-generate .htaccess if tit is missing
      'path' => '',// if not set will be the value of sys_get_temp_dir()
      "limited_memory_each_object" => 4096, // maximum size (bytes) of object store in memory
      "compress_data" => false, // compress stored data, if the backend supports it
    ];

    /**
     * @var string
     */
    protected static $namespacePath;

    /**
     * @var array
     */
    protected static $instances = [];

    /**
     * @param string $driver
     * @param array $config
     * @return ExtendedCacheItemPoolInterface
     */
    public static function getInstance($driver = 'auto', $config = [])
    {
        static $badPracticeOmeter = [];

        /**
         * @todo: Standardize a method for driver name
         */
        $driver = self::standardizeDriverName($driver);
        $config = array_merge(self::$config, $config);
        if (!$driver || $driver === 'Auto') {
            $driver = self::getAutoClass($config);
        }

        $instance = crc32($driver . serialize($config));
        if (!isset(self::$instances[ $instance ])) {
            $badPracticeOmeter[$driver] = 1;
            $class = self::getNamespacePath() . $driver . '\Driver';
            try{
                self::$instances[ $instance ] = new $class($config);
            }catch(phpFastCacheDriverCheckException $e){
                $fallback = self::standardizeDriverName($config['fallback']);
                if($fallback && $fallback !== $driver){
                    $class = self::getNamespacePath() . $fallback . '\Driver';
                    self::$instances[ $instance ] = new $class($config);
                    trigger_error(sprintf('The "%s" driver is unavailable at the moment, the fallback driver "%s" has been used instead.', $driver, $fallback), E_USER_WARNING);
                }else{
                    throw new phpFastCacheDriverCheckException($e->getMessage(), $e->getCode(), $e);
                }
            }
        } else if(++$badPracticeOmeter[$driver] >= 5){
           trigger_error('[' . $driver . '] Calling many times CacheManager::getInstance() for already instanced drivers is a bad practice and have a significant impact on performances.
           See https://github.com/PHPSocialNetwork/phpfastcache/wiki/[V5]-Why-calling-getInstance%28%29-each-time-is-a-bad-practice-%3F');
        }

        return self::$instances[ $instance ];
    }

    /**
     * @param $config
     * @return string
     * @throws phpFastCacheDriverCheckException
     */
    public static function getAutoClass($config = [])
    {
        static $autoDriver;

        if ($autoDriver === null) {
            foreach (self::getStaticSystemDrivers() as $driver) {
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

        gc_collect_cycles();
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
     * @deprecated Method "setup" is deprecated and will be removed in 5.1. Use method "setDefaultConfig" instead.
     */
    public static function setup($name, $value = '')
    {
        trigger_error('Method "setup" is deprecated and will be removed in 5.1. Use method "setDefaultConfig" instead.');
        self::setDefaultConfig($name, $value);
    }

    /**
     * @param $name string|array
     * @param mixed $value
     */
    public static function setDefaultConfig($name, $value = null)
    {
        if (is_array($name)) {
            self::$config = array_merge(self::$config, $name);
        } else if (is_string($name)){
            self::$config[ $name ] = $value;
        }else{
            throw new \InvalidArgumentException('Invalid variable type: $name');
        }
    }

    /**
     * @return array
     */
    public static function getDefaultConfig()
    {
        return self::$config;
    }

    /**
     * @return array
     */
    public static function getStaticSystemDrivers()
    {
        return [
          'Sqlite',
          'Files',
          'Apc',
          'Apcu',
          'Memcache',
          'Memcached',
          'Couchbase',
          'Mongodb',
          'Predis',
          'Redis',
          'Ssdb',
          'Leveldb',
          'Wincache',
          'Xcache',
          'Devnull',
        ];
    }

    /**
     * @return array
     */
    public static function getStaticAllDrivers()
    {
        return array_merge(self::getStaticSystemDrivers(), [
            'Devtrue',
            'Devfalse',
            'Cookie',
        ]);
    }

    /**
     * @param string $driverName
     * @return string
     */
    public static function standardizeDriverName($driverName)
    {
        return ucfirst(strtolower(trim($driverName)));
    }
}
