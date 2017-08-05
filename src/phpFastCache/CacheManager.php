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

/**
 * Class CacheManager
 * @package phpFastCache
 *
 * @method static ExtendedCacheItemPoolInterface Apc() Apc($config = []) Return a driver "apc" instance
 * @method static ExtendedCacheItemPoolInterface Apcu() Apcu($config = []) Return a driver "apcu" instance
 * @method static ExtendedCacheItemPoolInterface Cookie() Cookie($config = []) Return a driver "cookie" instance
 * @method static ExtendedCacheItemPoolInterface Couchbase() Couchbase($config = []) Return a driver "couchbase" instance
 * @method static ExtendedCacheItemPoolInterface Files() Files($config = []) Return a driver "files" instance
 * @method static ExtendedCacheItemPoolInterface Leveldb() Leveldb($config = []) Return a driver "leveldb" instance
 * @method static ExtendedCacheItemPoolInterface Memcache() Memcache($config = []) Return a driver "memcache" instance
 * @method static ExtendedCacheItemPoolInterface Memcached() Memcached($config = []) Return a driver "memcached" instance
 * @method static ExtendedCacheItemPoolInterface Mongodb() Mongodb($config = []) Return a driver "mongodb" instance
 * @method static ExtendedCacheItemPoolInterface Predis() Predis($config = []) Return a driver "predis" instance
 * @method static ExtendedCacheItemPoolInterface Redis() Redis($config = []) Return a driver "redis" instance
 * @method static ExtendedCacheItemPoolInterface Sqlite() Sqlite($config = []) Return a driver "sqlite" instance
 * @method static ExtendedCacheItemPoolInterface Ssdb() Ssdb($config = []) Return a driver "ssdb" instance
 * @method static ExtendedCacheItemPoolInterface Wincache() Wincache($config = []) Return a driver "wincache" instance
 * @method static ExtendedCacheItemPoolInterface Xcache() Xcache($config = []) Return a driver "xcache" instance
 * @method static ExtendedCacheItemPoolInterface Zenddisk() Zenddisk($config = []) Return a driver "zend disk cache" instance
 * @method static ExtendedCacheItemPoolInterface Zendshm() Zendshm($config = []) Return a driver "zend memory cache" instance
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
      'securityKey' => 'auto', // The securityKey that will be used to create the sub-directory
      'ignoreSymfonyNotice' => false, // Ignore Symfony notices for Symfony projects that do not makes use of PhpFastCache's Symfony Bundle
      'defaultTtl' => 900, // Default time-to-live in seconds
      'htaccess' => true, // Auto-generate .htaccess if it is missing
      'default_chmod' => 0777, // 0777 is recommended
      'path' => '', // If not set will be the value of sys_get_temp_dir()
      'fallback' => false, // Fall back when old driver is not supported
      'limited_memory_each_object' => 4096, // Maximum size (bytes) of object store in memory
      'compress_data' => false, // Compress stored data if the backend supports it
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
            if(!$config['ignoreSymfonyNotice'] && interface_exists('Symfony\Component\HttpKernel\KernelInterface') && !class_exists('phpFastCache\Bundle\phpFastCacheBundle')){
                trigger_error('A Symfony Bundle to make the PhpFastCache integration more easier is now available here: https://github.com/PHPSocialNetwork/phpfastcache-bundle', E_USER_NOTICE);
            }
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
                    break;
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
        self::$instances = [];

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
     * @deprecated Method "setup" is deprecated and will be removed in V6. Use method "setDefaultConfig" instead.
     * @throws \InvalidArgumentException
     */
    public static function setup($name, $value = '')
    {
        trigger_error('Method "setup" is deprecated and will be removed in V6 Use method "setDefaultConfig" instead.', E_USER_DEPRECATED);
        self::setDefaultConfig($name, $value);
    }

    /**
     * @param $name string|array
     * @param mixed $value
     * @throws \InvalidArgumentException
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
          'Zenddisk',
          'Zendshm',
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
