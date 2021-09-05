<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */
declare(strict_types=1);

namespace Phpfastcache;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Config\ConfigurationOptionInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheInstanceNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Exceptions\PhpfastcacheUnsupportedOperationException;
use Phpfastcache\Util\ClassNamespaceResolverTrait;

/**
 * Class CacheManager
 * @package phpFastCache
 *
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
 * @method static ExtendedCacheItemPoolInterface Zenddisk() Zenddisk($config = []) Return a driver "Zend disk cache" instance
 * @method static ExtendedCacheItemPoolInterface Zendshm() Zendshm($config = []) Return a driver "Zend memory cache" instance
 *
 */
class CacheManager
{
    public const CORE_DRIVER_NAMESPACE = 'Phpfastcache\Drivers\\';

    use ClassNamespaceResolverTrait;

    protected static ConfigurationOption $config;

    protected static string $namespacePath;

    /**
     * @var ExtendedCacheItemPoolInterface[]
     */
    protected static array $instances = [];

    /**
     * @var string[]
     */
    protected static array $driverOverrides = [];

    /**
     * @var string[]
     */
    protected static array $driverCustoms = [];

    /**
     * CacheManager constructor.
     */
    final protected function __construct()
    {
        // The cache manager is not meant to be instantiated
    }

    /**
     * @param string $instanceId
     * @return ExtendedCacheItemPoolInterface
     * @throws PhpfastcacheInstanceNotFoundException
     */
    public static function getInstanceById(string $instanceId): ExtendedCacheItemPoolInterface
    {
        if (isset(self::$instances[$instanceId])) {
            return self::$instances[$instanceId];
        }

        throw new PhpfastcacheInstanceNotFoundException(sprintf('Instance ID %s not found', $instanceId));
    }

    /**
     * Return the list of instances
     *
     * @return ExtendedCacheItemPoolInterface[]
     */
    public static function getInstances(): array
    {
        return self::$instances;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return ExtendedCacheItemPoolInterface
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): ExtendedCacheItemPoolInterface
    {
        $options = (\array_key_exists(0, $arguments) ? $arguments[0] : []);

        return self::getInstance($name, $options);
    }

    /**
     * @param string $driver
     * @param ConfigurationOptionInterface|null $config
     * @param string|null $instanceId
     * @return ExtendedCacheItemPoolInterface
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public static function getInstance(string $driver, ?ConfigurationOptionInterface $config = null, ?string $instanceId = null): ExtendedCacheItemPoolInterface
    {
        $config = self::validateConfig($config);
        $driver = self::standardizeDriverName($driver);

        $instanceId = $instanceId ?: md5($driver . \serialize(\array_filter($config->toArray(), static fn ($val) => !\is_callable($val))));

        if (!isset(self::$instances[$instanceId])) {
            $driverClass = self::validateDriverClass(self::getDriverClass($driver));

            if (\class_exists($driverClass)) {
                $configClass = $driverClass::getConfigClass();
                if ($configClass !== $config::class) {
                    $config = new $configClass($config->toArray());
                }
                self::$instances[$instanceId] = new $driverClass($config, $instanceId);
                self::$instances[$instanceId]->setEventManager(EventManager::getInstance());
            } else {
                throw new PhpfastcacheDriverNotFoundException(sprintf('The driver "%s" does not exists', $driver));
            }
        }

        return self::$instances[$instanceId];
    }

    /**
     * @param ConfigurationOption|null $config
     * @return ConfigurationOption
     */
    protected static function validateConfig(?ConfigurationOption $config): ConfigurationOption
    {
        return $config ?? self::getDefaultConfig();
    }

    /**
     * @return ConfigurationOption
     */
    public static function getDefaultConfig(): ConfigurationOption
    {
        return self::$config ?? self::$config = new ConfigurationOption();
    }

    /**
     * @param string $driverName
     * @return string
     */
    public static function standardizeDriverName(string $driverName): string
    {
        return \ucfirst(\strtolower(\trim($driverName)));
    }

    /**
     * @param string $driverClass
     * @return string
     * @throws PhpfastcacheDriverException
     */
    protected static function validateDriverClass(string $driverClass): string
    {
        if (!\is_a($driverClass, ExtendedCacheItemPoolInterface::class, true)) {
            throw new PhpfastcacheDriverException(
                \sprintf(
                    'Class "%s" does not implement "%s"',
                    $driverClass,
                    ExtendedCacheItemPoolInterface::class
                )
            );
        }
        return $driverClass;
    }

    /**
     * @param string $driverName
     * @return string
     */
    public static function getDriverClass(string $driverName): string
    {
        if (!empty(self::$driverCustoms[$driverName])) {
            $driverClass = self::$driverCustoms[$driverName];
        } elseif (!empty(self::$driverOverrides[$driverName])) {
            $driverClass = self::$driverOverrides[$driverName];
        } else {
            $driverClass = self::getNamespacePath() . $driverName . '\Driver';
        }

        return $driverClass;
    }

    /**
     * @return string
     */
    public static function getNamespacePath(): string
    {
        return self::$namespacePath ?? self::getDefaultNamespacePath();
    }

    /**
     * @return string
     */
    public static function getDefaultNamespacePath(): string
    {
        return self::CORE_DRIVER_NAMESPACE;
    }

    /**
     * @return bool
     */
    public static function clearInstances(): bool
    {
        self::$instances = [];

        \gc_collect_cycles();
        return !\count(self::$instances);
    }

    /**
     * @param ExtendedCacheItemPoolInterface $cachePoolInstance
     * @return bool
     * @since 7.0.4
     */
    public static function clearInstance(ExtendedCacheItemPoolInterface $cachePoolInstance): bool
    {
        $found = false;
        self::$instances = \array_filter(
            \array_map(
                static function (ExtendedCacheItemPoolInterface $cachePool) use ($cachePoolInstance, &$found) {
                    if (\spl_object_hash($cachePool) === \spl_object_hash($cachePoolInstance)) {
                        $found = true;
                        return null;
                    }
                    return $cachePool;
                },
                self::$instances
            )
        );

        return $found;
    }

    /**
     * @param ConfigurationOption $config
     */
    public static function setDefaultConfig(ConfigurationOption $config): void
    {
        if(is_subclass_of($config, ConfigurationOption::class)){
            throw new PhpfastcacheInvalidArgumentException('Default configuration cannot be a child class of ConfigurationOption::class');
        }
        self::$config = $config;
    }

    /**
     * @param string $driverName
     * @param string $className
     * @return void
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheUnsupportedOperationException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public static function addCustomDriver(string $driverName, string $className): void
    {
        $driverName = self::standardizeDriverName($driverName);

        if (empty($driverName)) {
            throw new PhpfastcacheInvalidArgumentException("Can't add a custom driver because its name is empty");
        }

        if (!\class_exists($className)) {
            throw new PhpfastcacheInvalidArgumentException(
                \sprintf("Can't add '%s' because the class '%s' does not exists", $driverName, $className)
            );
        }

        if (!empty(self::$driverCustoms[$driverName])) {
            throw new PhpfastcacheLogicException(\sprintf("Driver '%s' has been already added", $driverName));
        }

        if (\in_array($driverName, self::getDriverList(), true)) {
            throw new PhpfastcacheLogicException(\sprintf("Driver '%s' is already a part of the Phpfastcache core", $driverName));
        }

        self::$driverCustoms[$driverName] = $className;
    }

    /**
     * Return the list of available drivers Capitalized
     * with optional FQCN as key
     *
     * @param bool $fqcnAsKey Describe keys with Full Qualified Class Name
     * @return string[]
     * @throws PhpfastcacheUnsupportedOperationException
     */
    public static function getDriverList(bool $fqcnAsKey = false): array
    {
        static $driverList;

        if (self::getDefaultNamespacePath() === self::getNamespacePath()) {
            if ($driverList === null) {
                $prefix = self::CORE_DRIVER_NAMESPACE;
                $classMap = self::createClassMap(__DIR__ . '/Drivers');
                $driverList = [];

                foreach (\array_keys($classMap) as $class) {
                    $driverList[] = \str_replace($prefix, '', \substr($class, 0, \strrpos($class, '\\')));
                }

                $driverList = \array_values(\array_unique($driverList));
            }

            $driverList = \array_merge($driverList, \array_keys(self::$driverCustoms));

            if ($fqcnAsKey) {
                $realDriverList = [];
                foreach ($driverList as $driverName) {
                    $realDriverList[self::getDriverClass($driverName)] = $driverName;
                }
                $driverList = $realDriverList;
            }

            \asort($driverList);

            return $driverList;
        }

        throw new PhpfastcacheUnsupportedOperationException('Cannot get the driver list if the default namespace path has changed.');
    }

    /**
     * @param string $driverName
     * @return void
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public static function removeCustomDriver(string $driverName): void
    {
        $driverName = self::standardizeDriverName($driverName);

        if (empty($driverName)) {
            throw new PhpfastcacheInvalidArgumentException("Can't remove a custom driver because its name is empty");
        }

        if (!isset(self::$driverCustoms[$driverName])) {
            throw new PhpfastcacheLogicException(\sprintf("Driver '%s' does not exists", $driverName));
        }

        unset(self::$driverCustoms[$driverName]);
    }

    /**
     * @param string $driverName
     * @param string $className
     * @return void
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheUnsupportedOperationException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public static function addCoreDriverOverride(string $driverName, string $className): void
    {
        $driverName = self::standardizeDriverName($driverName);

        if (empty($driverName)) {
            throw new PhpfastcacheInvalidArgumentException("Can't add a core driver override because its name is empty");
        }

        if (!\class_exists($className)) {
            throw new PhpfastcacheInvalidArgumentException(
                \sprintf("Can't override '%s' because the class '%s' does not exists", $driverName, $className)
            );
        }

        if (!empty(self::$driverOverrides[$driverName])) {
            throw new PhpfastcacheLogicException(\sprintf("Driver '%s' has been already overridden", $driverName));
        }

        if (!\in_array($driverName, self::getDriverList(), true)) {
            throw new PhpfastcacheLogicException(\sprintf("Driver '%s' can't be overridden since its not a part of the Phpfastcache core", $driverName));
        }

        if (!\is_subclass_of($className, self::CORE_DRIVER_NAMESPACE . $driverName . '\\Driver', true)) {
            throw new PhpfastcacheLogicException(
                \sprintf(
                    "Can't override '%s' because the class '%s' MUST extend '%s'",
                    $driverName,
                    $className,
                    self::CORE_DRIVER_NAMESPACE . $driverName . '\\Driver'
                )
            );
        }

        self::$driverOverrides[$driverName] = $className;
    }

    /**
     * @param string $driverName
     * @return void
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public static function removeCoreDriverOverride(string $driverName): void
    {
        $driverName = self::standardizeDriverName($driverName);

        if (empty($driverName)) {
            throw new PhpfastcacheInvalidArgumentException("Can't remove a core driver override because its name is empty");
        }

        if (!isset(self::$driverOverrides[$driverName])) {
            throw new PhpfastcacheLogicException(\sprintf("Driver '%s' were not overridden", $driverName));
        }

        unset(self::$driverOverrides[$driverName]);
    }
}
