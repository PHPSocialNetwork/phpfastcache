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

use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheInvalidConfigurationException;

/**
 * Class CacheManager
 * @package phpFastCache
 *
 * @method static ExtendedCacheItemPoolInterface Apc() Apc($config = []) Return a driver "Apc" instance
 * @method static ExtendedCacheItemPoolInterface Apcu() Apcu($config = []) Return a driver "Apcu" instance
 * @method static ExtendedCacheItemPoolInterface Cassandra() Cassandra($config = []) Return a driver "Cassandra" instance
 * @method static ExtendedCacheItemPoolInterface Cookie() Cookie($config = []) Return a driver "Cookie" instance
 * @method static ExtendedCacheItemPoolInterface Couchbase() Couchbase($config = []) Return a driver "Couchbase" instance
 * @method static ExtendedCacheItemPoolInterface Couchdb() Couchdb($config = []) Return a driver "Couchdb" instance
 * @method static ExtendedCacheItemPoolInterface Devnull() Devnull($config = []) Return a driver "Devnull" instance
 * @method static ExtendedCacheItemPoolInterface Files() Files($config = []) Return a driver "files" instance
 * @method static ExtendedCacheItemPoolInterface Leveldb() Leveldb($config = []) Return a driver "Leveldb" instance
 * @method static ExtendedCacheItemPoolInterface Memcache() Memcache($config = []) Return a driver "Memcache" instance
 * @method static ExtendedCacheItemPoolInterface Memcached() Memcached($config = []) Return a driver "Memcached" instance
 * @method static ExtendedCacheItemPoolInterface Memstatic() Memstatic($config = []) Return a driver "Memstatic" instance
 * @method static ExtendedCacheItemPoolInterface Mongodb() Mongodb($config = []) Return a driver "Mongodb" instance
 * @method static ExtendedCacheItemPoolInterface Predis() Predis($config = []) Return a driver "Predis" instance
 * @method static ExtendedCacheItemPoolInterface Redis() Redis($config = []) Return a driver "Pedis" instance
 * @method static ExtendedCacheItemPoolInterface Sqlite() Sqlite($config = []) Return a driver "Sqlite" instance
 * @method static ExtendedCacheItemPoolInterface Ssdb() Ssdb($config = []) Return a driver "Ssdb" instance
 * @method static ExtendedCacheItemPoolInterface Wincache() Wincache($config = []) Return a driver "Wincache" instance
 * @method static ExtendedCacheItemPoolInterface Xcache() Xcache($config = []) Return a driver "Xcache" instance
 * @method static ExtendedCacheItemPoolInterface Zenddisk() Zenddisk($config = []) Return a driver "Zend disk cache" instance
 * @method static ExtendedCacheItemPoolInterface Zendshm() Zendshm($config = []) Return a driver "Zend memory cache" instance
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
        /**
         * Specify if the item must provide detailed creation/modification dates
         */
      'itemDetailedDate' => false,

        /**
         * Automatically attempt to fallback to temporary directory
         * if the cache fails to write on the specified directory
         */
      'autoTmpFallback' => false,

        /**
         * Provide a secure file manipulation mechanism,
         * on intensive usage the performance can be affected.
         */
      'secureFileManipulation' => false,

        /**
         * Ignore Symfony notice for Symfony project which
         * do not makes use of PhpFastCache's Symfony Bundle
         */
      'ignoreSymfonyNotice' => false,

        /**
         * Default time-to-live in second
         */
      'defaultTtl' => 900,

        /**
         * Default key hash function
         * (md5 by default)
         */
      'defaultKeyHashFunction' => '',

        /**
         * The securityKey that will be used
         * to create sub-directory
         * (Files-based drivers only)
         */
      'securityKey' => 'Auto',

        /**
         * Auto-generate .htaccess if it's missing
         * (Files-based drivers only)
         */
      'htaccess' => true,

        /**
         * Default files chmod
         * 0777 recommended
         * (Files-based drivers only)
         */
      'default_chmod' => 0777,

        /**
         * The path where we will writecache files
         * default value if empty: sys_get_temp_dir()
         * (Files-based drivers only)
         */
      'path' => '',

        /**
         * Driver fallback in case of failure.
         * Caution, in case of failure an E_WARNING
         * error will always be raised
         */
      'fallback' => false,

        /**
         * Maximum size (bytes) of object store in memory
         * (Memcache(d) drivers only)
         */
      'limited_memory_each_object' => 4096,

        /**
         * Compress stored data, if the backend supports it
         * (Memcache(d) drivers only)
         */
      'compress_data' => false,

        /**
         * Prevent cache slams when
         * making use of heavy cache
         * items
         */
      'preventCacheSlams' => false,

        /**
         * Cache slams timeout
         * in seconds
         */
      'cacheSlamsTimeout' => 15,

        /**
         * Cache slams timeout
         * in seconds
         */
      'cacheFileExtension' => 'txt',

    ];

    /**
     * Feel free to propose your own one
     * by opening a pull request :)
     * @var array
     */
    protected static $safeFileExtensions = [
      'txt',
      'cache',
      'db',
      'pfc',
    ];

    /**
     * @var string
     */
    protected static $namespacePath;

    /**
     * @var ExtendedCacheItemPoolInterface[]
     */
    protected static $instances = [];

    /**
     * @param string $driver
     * @param array $config
     * @return ExtendedCacheItemPoolInterface
     * @throws phpFastCacheDriverCheckException
     * @throws phpFastCacheInvalidConfigurationException
     */
    public static function getInstance($driver = 'auto', array $config = [])
    {
        static $badPracticeOmeter = [];

        /**
         * @todo: Standardize a method for driver name
         */
        $driver = self::standardizeDriverName($driver);
        $config = array_merge(self::$config, $config);
        self::validateConfig($config);
        if (!$driver || $driver === 'Auto') {
            $driver = self::getAutoClass($config);
        }

        $instance = crc32($driver . serialize($config));
        if (!isset(self::$instances[ $instance ])) {
            $badPracticeOmeter[ $driver ] = 1;
            if (!$config[ 'ignoreSymfonyNotice' ] && interface_exists('Symfony\Component\HttpKernel\KernelInterface') && !class_exists('phpFastCache\Bundle\phpFastCacheBundle')) {
                trigger_error('A Symfony Bundle to make the PhpFastCache integration more easier is now available here: https://github.com/PHPSocialNetwork/phpfastcache-bundle',
                  E_USER_NOTICE);
            }
            $class = self::getNamespacePath() . $driver . '\Driver';
            try {
                self::$instances[ $instance ] = new $class($config);
                self::$instances[ $instance ]->setEventManager(EventManager::getInstance());
            } catch (phpFastCacheDriverCheckException $e) {
                if ($config[ 'fallback' ]) {
                    try {
                        $fallback = self::standardizeDriverName($config[ 'fallback' ]);
                        if ($fallback !== $driver) {
                            $class = self::getNamespacePath() . $fallback . '\Driver';
                            self::$instances[ $instance ] = new $class($config);
                            self::$instances[ $instance ]->setEventManager(EventManager::getInstance());
                            trigger_error(sprintf('The "%s" driver is unavailable at the moment, the fallback driver "%s" has been used instead.', $driver,
                              $fallback), E_USER_WARNING);
                        } else {
                            throw new phpFastCacheInvalidConfigurationException('The fallback driver cannot be the same than the default driver', 0, $e);
                        }
                    } catch (phpFastCacheInvalidArgumentException $e) {
                        throw new phpFastCacheInvalidConfigurationException('Invalid fallback driver configuration', 0, $e);
                    }
                } else {
                    throw new phpFastCacheDriverCheckException($e->getMessage(), $e->getCode(), $e);
                }
            }
        } else if ($badPracticeOmeter[ $driver ] >= 5) {
            trigger_error('[' . $driver . '] Calling many times CacheManager::getInstance() for already instanced drivers is a bad practice and have a significant impact on performances.
           See https://github.com/PHPSocialNetwork/phpfastcache/wiki/[V5]-Why-calling-getInstance%28%29-each-time-is-a-bad-practice-%3F');
        }

        $badPracticeOmeter[ $driver ]++;

        return self::$instances[ $instance ];
    }

    /**
     * This method is intended for internal
     * use only and should not be used for
     * any external development use the
     * getInstances() method instead
     *
     * @internal
     * @return ExtendedCacheItemPoolInterface[]
     */
    public static function getInstances()
    {
        return self::$instances;
    }

    /**
     * This method is intended for internal
     * use only and should not be used for
     * any external development use the
     * getInstances() method instead
     *
     * @internal
     * @return ExtendedCacheItemPoolInterface[]
     */
    public static function &getInternalInstances()
    {
        return self::$instances;
    }

    /**
     * @param $config
     * @return string
     * @throws phpFastCacheDriverCheckException
     */
    public static function getAutoClass(array $config = [])
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
        self::$namespacePath = trim($path, "\\") . '\\';
    }

    /**
     * @param $name string|array
     * @param mixed $value
     * @throws phpFastCacheInvalidArgumentException
     */
    public static function setDefaultConfig($name, $value = null)
    {
        if (is_array($name)) {
            self::$config = array_merge(self::$config, $name);
        } else if (is_string($name)) {
            self::$config[ $name ] = $value;
        } else {
            throw new phpFastCacheInvalidArgumentException('Invalid variable type: $name');
        }
    }

    /**
     * @param $name string|array
     * @param mixed $value
     * @throws phpFastCacheInvalidConfigurationException
     * @deprecated Method "setup" is deprecated, please use "setDefaultConfig" method instead
     */
    public static function setup($name, $value = null)
    {
        throw new phpFastCacheInvalidConfigurationException(sprintf('Method "%s" is deprecated, please use "setDefaultConfig" method instead.', __FUNCTION__));
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
          'Apc',
          'Apcu',
          'Cassandra',
          'Couchbase',
          'Couchdb',
          'Devnull',
          'Files',
          'Leveldb',
          'Memcache',
          'Memcached',
          'Memstatic',
          'Mongodb',
          'Predis',
          'Redis',
          'Ssdb',
          'Sqlite',
          'Wincache',
          'Xcache',
          'Zenddisk',
          'Zendshm',
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
     * @param $driverName
     * @return string
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidArgumentException
     */
    public static function standardizeDriverName($driverName)
    {
        if (!is_string($driverName)) {
            throw new phpFastCacheInvalidArgumentException(sprintf('Expected $driverName to be a string got "%s" instead', gettype($driverName)));
        }
        return ucfirst(strtolower(trim($driverName)));
    }

    /**
     * @param array $config
     * @todo Move this to a config file
     * @throws phpFastCacheInvalidConfigurationException
     * @return bool
     */
    protected static function validateConfig(array $config)
    {
        foreach ($config as $configName => $configValue) {
            switch ($configName) {
                case 'itemDetailedDate':
                case 'autoTmpFallback':
                case 'secureFileManipulation':
                case 'ignoreSymfonyNotice':
                case 'htaccess':
                case 'compress_data':
                    if (!is_bool($configValue)) {
                        throw new phpFastCacheInvalidConfigurationException("{$configName} must be a boolean");
                    }
                    break;
                case 'defaultTtl':
                    if (!is_numeric($configValue)) {
                        throw new phpFastCacheInvalidConfigurationException("{$configName} must be numeric");
                    }
                    break;
                case 'defaultKeyHashFunction':
                    if (!is_string($configValue) && !function_exists($configValue)) {
                        throw new phpFastCacheInvalidConfigurationException("{$configName} must be a valid function name string");
                    }
                    break;
                case 'securityKey':
                case 'path':
                    if (!is_string($configValue) && (!is_bool($configValue) || $configValue)) {
                        throw new phpFastCacheInvalidConfigurationException("{$configName} must be a string or a false boolean");
                    }
                    break;
                case 'default_chmod':
                case 'limited_memory_each_object':
                    if (!is_int($configValue)) {
                        throw new phpFastCacheInvalidConfigurationException("{$configName} must be an integer");
                    }
                    break;
                case 'fallback':
                    if (!is_bool($configValue) && !is_string($configValue)) {
                        throw new phpFastCacheInvalidConfigurationException("{$configName} must be a boolean or string");
                    }
                    break;
                case 'cacheFileExtension':
                    if (!is_string($configValue)) {
                        throw new phpFastCacheInvalidConfigurationException("{$configName} must be a boolean");
                    }
                    if (strpos($configValue, '.') !== false) {
                        throw new phpFastCacheInvalidConfigurationException("{$configName} cannot contain a dot \".\"");
                    }
                    if (!in_array($configValue, self::$safeFileExtensions)) {
                        throw new phpFastCacheInvalidConfigurationException(
                            "{$configName} is not a safe extension, currently allowed extension: " . implode(', ', self::$safeFileExtensions)
                        );
                    }
                    break;
            }
        }

        return true;
    }
}
