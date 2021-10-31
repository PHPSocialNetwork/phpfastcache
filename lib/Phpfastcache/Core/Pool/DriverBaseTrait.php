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

namespace Phpfastcache\Core\Pool;

use DateTime;
use DateTimeInterface;
use Phpfastcache\Config\ConfigurationOptionInterface;
use Phpfastcache\Event\EventManagerDispatcherTrait;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Util\ClassNamespaceResolverTrait;
use Throwable;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use ReflectionObject;

trait DriverBaseTrait
{
    use DriverPoolAbstractTrait;
    use ClassNamespaceResolverTrait;
    use EventManagerDispatcherTrait;

    protected static array $cacheItemClasses = [];

    protected ConfigurationOptionInterface $config;

    protected object|array|null $instance;

    protected string $driverName;

    protected string $instanceId;

    /**
     * Driver constructor.
     * @param ConfigurationOptionInterface $config
     * @param string $instanceId
     * @param EventManagerInterface $em
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverConnectException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function __construct(ConfigurationOptionInterface $config, string $instanceId, EventManagerInterface $em)
    {
        $this->setEventManager($em);
        $this->setConfig($config);
        $this->instanceId = $instanceId;
        $this->IO = new DriverIO();

        if (!$this->driverCheck()) {
            throw new PhpfastcacheDriverCheckException(\sprintf(self::DRIVER_CHECK_FAILURE, $this->getDriverName()));
        }

        try {
            $this->driverConnect();
            $config->lock($this);
        } catch (Throwable $e) {
            throw new PhpfastcacheDriverConnectException(
                sprintf(
                    self::DRIVER_CONNECT_FAILURE,
                    $e::class,
                    $this->getDriverName(),
                    $e->getMessage(),
                    $e->getLine() ?: 'unknown line',
                    $e->getFile() ?: 'unknown file'
                ),
                0,
                $e
            );
        }
    }

    /**
     * @return string
     */
    public function getDriverName(): string
    {
        if (!isset($this->driverName)) {
            $this->driverName = \ucfirst(\substr(\strrchr((new ReflectionObject($this))->getNamespaceName(), '\\'), 1));
        }
        return $this->driverName;
    }

    /**
     * @return ConfigurationOption
     */
    public function getDefaultConfig(): ConfigurationOption
    {
        $className = $this::getConfigClass();

        return new $className();
    }

    /**
     * @return string
     */
    public static function getConfigClass(): string
    {
        $localConfigClass = \substr(static::class, 0, \strrpos(static::class, '\\')) . '\Config';
        if (\class_exists($localConfigClass) && \is_a($localConfigClass, ConfigurationOption::class, true)) {
            return $localConfigClass;
        }
        return ConfigurationOption::class;
    }

    public static function getItemClass(): string
    {
        if (!isset(self::$cacheItemClasses[static::class])) {
            self::$cacheItemClasses[static::class] = self::getClassNamespace() . '\\' . 'Item';
        }

        return self::$cacheItemClasses[static::class];
    }


    /**
     * @param ExtendedCacheItemInterface $item
     * @param bool $stringifyDate
     * @return array
     * @throws PhpfastcacheLogicException
     */
    public function driverPreWrap(ExtendedCacheItemInterface $item, bool $stringifyDate = false): array
    {
        $wrap = [
            self::DRIVER_KEY_WRAPPER_INDEX => $item->getKey(), // Stored but not really used, allow you to quickly identify the cache key
            self::DRIVER_DATA_WRAPPER_INDEX => $item->getRawValue(),
            self::DRIVER_TAGS_WRAPPER_INDEX => $item->getTags(),
            self::DRIVER_EDATE_WRAPPER_INDEX => $item->getExpirationDate(),
        ];

        if ($this->getConfig()->isItemDetailedDate()) {
            $wrap[self::DRIVER_MDATE_WRAPPER_INDEX] = new DateTime();
            /**
             * If the creation date exists
             * reuse it else set a new Date
             */
            $wrap[self::DRIVER_CDATE_WRAPPER_INDEX] = $item->getCreationDate() ?: new DateTime();
        } else {
            $wrap[self::DRIVER_MDATE_WRAPPER_INDEX] = null;
            $wrap[self::DRIVER_CDATE_WRAPPER_INDEX] = null;
        }

        if ($stringifyDate) {
            $wrap = \array_map(static function ($value) {
                if ($value instanceof DateTimeInterface) {
                    return $value->format(DateTimeInterface::W3C);
                }
                return $value;
            }, $wrap);
        }

        return $wrap;
    }

    /**
     * @return ConfigurationOption
     */
    abstract public function getConfig(): ConfigurationOption;

    /**
     * @param ConfigurationOptionInterface $config
     * @return static
     */
    public function setConfig(ConfigurationOptionInterface $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param array $wrapper
     * @return mixed
     * @throws \Exception
     */
    public function driverUnwrapData(array $wrapper): mixed
    {
        return $wrapper[self::DRIVER_DATA_WRAPPER_INDEX];
    }

    /**
     * @param array $wrapper
     * @return DateTime
     */
    public function driverUnwrapEdate(array $wrapper): \DateTime
    {
        if ($wrapper[self::DRIVER_EDATE_WRAPPER_INDEX] instanceof \DateTime) {
            return $wrapper[self::DRIVER_EDATE_WRAPPER_INDEX];
        }

        return DateTime::createFromFormat(\DateTimeInterface::W3C, $wrapper[self::DRIVER_EDATE_WRAPPER_INDEX]);
    }

    /**
     * @param array $wrapper
     * @return DateTime|null
     */
    public function driverUnwrapCdate(array $wrapper): ?\DateTime
    {
        if ($wrapper[self::DRIVER_CDATE_WRAPPER_INDEX] instanceof \DateTime) {
            return $wrapper[self::DRIVER_CDATE_WRAPPER_INDEX];
        }

        return DateTime::createFromFormat(\DateTimeInterface::W3C, $wrapper[self::DRIVER_CDATE_WRAPPER_INDEX]);
    }

    /**
     * @param array $wrapper
     * @return DateTime|null
     */
    public function driverUnwrapMdate(array $wrapper): ?\DateTime
    {
        if ($wrapper[self::DRIVER_MDATE_WRAPPER_INDEX] instanceof \DateTime) {
            return $wrapper[self::DRIVER_MDATE_WRAPPER_INDEX];
        }

        return DateTime::createFromFormat(\DateTimeInterface::W3C, $wrapper[self::DRIVER_MDATE_WRAPPER_INDEX]);
    }

    /**
     * @return string
     */
    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    /**
     * Encode data types such as object/array
     * for driver that does not support
     * non-scalar value
     * @param $data
     * @return string
     */
    protected function encode($data): string
    {
        return \serialize($data);
    }

    /**
     * Decode data types such as object/array
     * for driver that does not support
     * non-scalar value
     * @param string|null $value
     * @return mixed
     */
    protected function decode(?string $value): mixed
    {
        return \unserialize((string) $value, ['allowed_classes' => true]);
    }
}
