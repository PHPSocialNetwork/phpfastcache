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
use Phpfastcache\Helper\UninstanciableObjectTrait;
use Phpfastcache\Util\ClassNamespaceResolverTrait;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CacheManager
{
    use ClassNamespaceResolverTrait;
    use UninstanciableObjectTrait;

    public const CORE_DRIVER_NAMESPACE = 'Phpfastcache\Drivers\\';

    protected static ConfigurationOptionInterface $config;

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
     * @param string $driver
     * @param ConfigurationOptionInterface|null $config
     * @param string|null $instanceId
     * @return ExtendedCacheItemPoolInterface
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheLogicException
     */
    public static function getInstance(string $driver, ?ConfigurationOptionInterface $config = null, ?string $instanceId = null): ExtendedCacheItemPoolInterface
    {
        if (\class_exists($driver) && \str_starts_with($driver, 'Phpfastcache')) {
            $driverClass = $driver;
        } else {
            $driver = self::normalizeDriverName($driver);
            $driverClass = self::validateDriverClass(self::getDriverClass($driver));
        }
        $config = self::validateConfig($config);
        $instanceId = $instanceId ?: self::getInstanceHash($driverClass, $config);

        if (!isset(self::$instances[$instanceId])) {
            if (\is_a($driverClass, ExtendedCacheItemPoolInterface::class, true)) {
                if (($configClass = $driverClass::getConfigClass()) !== $config::class) {
                    $config = new $configClass($config->toArray());
                }
                self::$instances[$instanceId] = new $driverClass(
                    $config,
                    $instanceId,
                    EventManager::getInstance()
                );
            } else {
                throw new PhpfastcacheDriverNotFoundException(sprintf(
                    'The driver "%s" does not exist or does not implement %s',
                    $driver,
                    ExtendedCacheItemPoolInterface::class
                ));
            }
        }

        return self::$instances[$instanceId];
    }

    /**
     * @param string $driverClass
     * @param ConfigurationOptionInterface $config
     * @return string
     */
    protected static function getInstanceHash(string $driverClass, ConfigurationOptionInterface $config): string
    {
        return \md5($driverClass . \serialize(
            \array_filter(
                $config->toArray(),
                static fn ($val) => $config->isValueSerializable($val)
            )
        ));
    }

    /**
     * @param ConfigurationOptionInterface|null $config
     * @return ConfigurationOptionInterface
     * @throws PhpfastcacheLogicException
     */
    protected static function validateConfig(?ConfigurationOptionInterface $config): ConfigurationOptionInterface
    {
        if ($config instanceof ConfigurationOptionInterface && $config->isLocked()) {
            throw new PhpfastcacheLogicException('You provided an already locked configuration, cannot continue.');
        }
        return $config ?? self::getDefaultConfig();
    }

    /**
     * @return ConfigurationOptionInterface
     */
    public static function getDefaultConfig(): ConfigurationOptionInterface
    {
        return self::$config ?? self::$config = new ConfigurationOption();
    }

    /**
     * @param string $driverName
     * @return string
     */
    public static function normalizeDriverName(string $driverName): string
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

        return true;
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
     * @param ConfigurationOptionInterface $config
     * @throws PhpfastcacheInvalidArgumentException
     */
    public static function setDefaultConfig(ConfigurationOptionInterface $config): void
    {
        if (\is_subclass_of($config, ConfigurationOption::class)) {
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
        $driverName = self::normalizeDriverName($driverName);

        if (empty($driverName)) {
            throw new PhpfastcacheInvalidArgumentException("Can't add a custom driver because its name is empty");
        }

        if (!\class_exists($className)) {
            throw new PhpfastcacheInvalidArgumentException(
                \sprintf("Can't add '%s' because the class '%s' does not exist", $driverName, $className)
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
        $driverName = self::normalizeDriverName($driverName);

        if (empty($driverName)) {
            throw new PhpfastcacheInvalidArgumentException("Can't remove a custom driver because its name is empty");
        }

        if (!isset(self::$driverCustoms[$driverName])) {
            throw new PhpfastcacheLogicException(\sprintf("Driver '%s' does not exist", $driverName));
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
        $driverName = self::normalizeDriverName($driverName);

        if (empty($driverName)) {
            throw new PhpfastcacheInvalidArgumentException("Can't add a core driver override because its name is empty");
        }

        if (!\class_exists($className)) {
            throw new PhpfastcacheInvalidArgumentException(
                \sprintf("Can't override '%s' because the class '%s' does not exist", $driverName, $className)
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
        $driverName = self::normalizeDriverName($driverName);

        if (empty($driverName)) {
            throw new PhpfastcacheInvalidArgumentException("Can't remove a core driver override because its name is empty");
        }

        if (!isset(self::$driverOverrides[$driverName])) {
            throw new PhpfastcacheLogicException(\sprintf("Driver '%s' were not overridden", $driverName));
        }

        unset(self::$driverOverrides[$driverName]);
    }
}
