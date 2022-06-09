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

    /**
     * @var string[]
     */
    protected static array $cacheItemClasses = [];

    protected ConfigurationOptionInterface $config;

    /**
     * @var object|array<mixed>|null
     */
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

        if (!$this->driverCheck()) {
            throw new PhpfastcacheDriverCheckException(\sprintf(ExtendedCacheItemPoolInterface::DRIVER_CHECK_FAILURE, $this->getDriverName()));
        }

        try {
            $this->driverConnect();
            $config->lock($this);
        } catch (Throwable $e) {
            throw new PhpfastcacheDriverConnectException(
                sprintf(
                    ExtendedCacheItemPoolInterface::DRIVER_CONNECT_FAILURE,
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
     * @return ConfigurationOptionInterface
     */
    public function getDefaultConfig(): ConfigurationOptionInterface
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
     * @return array<string, mixed>
     * @throws PhpfastcacheLogicException
     */
    public function driverPreWrap(ExtendedCacheItemInterface $item, bool $stringifyDate = false): array
    {
        $wrap = [
            ExtendedCacheItemPoolInterface::DRIVER_KEY_WRAPPER_INDEX => $item->getKey(), // Stored but not really used, allow you to quickly identify the cache key
            ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX => $item->getRawValue(),
            ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX => $item->getExpirationDate(),
            TaggableCacheItemPoolInterface::DRIVER_TAGS_WRAPPER_INDEX => $item->getTags(),
        ];

        if ($this->getConfig()->isItemDetailedDate()) {
            $wrap[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] = new DateTime();// Always on the latest date
            /**
             * If the creation date exists
             * reuse it else set a new Date
             */
            $wrap[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] = $item->getCreationDate();
        } else {
            $wrap[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] = null;
            $wrap[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] = null;
        }

        if ($stringifyDate) {
            \array_walk($wrap, static function (mixed &$value, string $key): void {
                if ($value instanceof DateTimeInterface && $key !== ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX) {
                    $value = $value->format(DateTimeInterface::W3C);
                }
            });
        }

        return $wrap;
    }

    /**
     * @return ConfigurationOptionInterface
     */
    public function getConfig(): ConfigurationOptionInterface
    {
        return $this->config;
    }

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
     * @param array<string, mixed> $wrapper
     * @return mixed
     * @throws \Exception
     */
    public function driverUnwrapData(array $wrapper): mixed
    {
        return $wrapper[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX];
    }

    /**
     * @param array<string, mixed> $wrapper
     * @return DateTimeInterface
     */
    public function driverUnwrapEdate(array $wrapper): \DateTimeInterface
    {
        if ($wrapper[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX] instanceof \DateTimeInterface) {
            return $wrapper[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX];
        }

        return DateTime::createFromFormat(\DateTimeInterface::W3C, $wrapper[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX]);
    }

    /**
     * @param array<string, mixed> $wrapper
     * @return DateTimeInterface|null
     */
    public function driverUnwrapCdate(array $wrapper): ?\DateTimeInterface
    {
        if ($wrapper[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] instanceof \DateTimeInterface) {
            return $wrapper[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX];
        }

        return DateTime::createFromFormat(\DateTimeInterface::W3C, $wrapper[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX]);
    }

    /**
     * @param array<string, mixed> $wrapper
     * @return DateTimeInterface|null
     */
    public function driverUnwrapMdate(array $wrapper): ?\DateTimeInterface
    {
        if ($wrapper[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] instanceof \DateTimeInterface) {
            return $wrapper[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX];
        }

        return DateTime::createFromFormat(\DateTimeInterface::W3C, $wrapper[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX]);
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
     * @param mixed $data
     * @return string
     */
    protected function encode(mixed $data): string
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
        return $value ? \unserialize($value, ['allowed_classes' => true]) : null;
    }
}
