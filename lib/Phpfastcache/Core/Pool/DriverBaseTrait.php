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
use Phpfastcache\Event\Event;
use Phpfastcache\Event\EventManagerDispatcherTrait;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheCorruptedDataException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
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
     * @var object|null
     */
    protected ?object $instance;

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
    public function __construct(#[\SensitiveParameter] ConfigurationOptionInterface $config, string $instanceId, EventManagerInterface $em)
    {
        $this->setEventManager($em->getScopedEventManager($this));
        $this->setConfig($config);
        $this->instanceId = $instanceId;

        if (!$this->driverCheck()) {
            throw new PhpfastcacheDriverCheckException(
                \sprintf(
                    ExtendedCacheItemPoolInterface::DRIVER_CHECK_FAILURE,
                    $this->getDriverName(),
                    $this->getHelp() ? " Additionally, {$this->getHelp()}" : ''
                )
            );
        }
        $this->eventManager->dispatch(Event::CACHE_DRIVER_CHECKED, $this);

        try {
            $this->driverConnect();
            $config->lock($this); // Lock the config only after a successful driver connection.
            $this->eventManager->dispatch(Event::CACHE_DRIVER_CONNECTED, $this, $this->instance ?? null);
        } catch (Throwable $e) {
            throw new PhpfastcacheDriverConnectException(
                sprintf(
                    ExtendedCacheItemPoolInterface::DRIVER_CONNECT_FAILURE,
                    $this->getDriverName(),
                    $e->getMessage(),
                    $e->getLine() ?: 'unknown line',
                    $e->getFile() ?: 'unknown file',
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
     * @inheritDoc
     */
    public function getEncodedKey(string $key): string
    {
        $keyHashFunction = $this->getConfig()->getDefaultKeyHashFunction();

        if ($keyHashFunction) {
            if (\is_callable($keyHashFunction)) {
                return $keyHashFunction($key);
            }
            throw new PhpfastcacheLogicException('Unable to build the encoded key (defaultKeyHashFunction is not callable)');
        }

        return $key;
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
            ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX => $item->_getData(),
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
     * Decode data stored in the cache
     * for driver that does not support
     * non-scalar value storage.
     * @param string|null $value
     * @return array<string, mixed>|null
     * @throws PhpfastcacheDriverException
     */
    protected function decode(?string $value): ?array
    {
        $decoded = $this->unserialize($value);

        if ($decoded === null || is_array($decoded)) {
            return $decoded;
        }
        throw new PhpfastcacheCorruptedDataException(
            sprintf(
                'Failed to unserialize data from the cache, expected array or null but got "%s". Stored data may be corrupted.',
                gettype($decoded)
            ),
            $value
        );
    }

    protected function unserialize(?string $value): mixed
    {
        return $value ? \unserialize($value, ['allowed_classes' => true]) : null;
    }
}
