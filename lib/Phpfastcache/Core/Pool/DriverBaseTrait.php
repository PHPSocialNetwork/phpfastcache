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
use Phpfastcache\Config\ConfigurationOptionInterface;
use Throwable;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use ReflectionObject;

trait DriverBaseTrait
{
    use DriverPoolAbstractTrait;

    protected ConfigurationOption $config;

    protected object|array|null $instance;

    protected string $driverName;

    protected string $instanceId;

    /**
     * Driver constructor.
     * @param ConfigurationOption $config
     * @param string $instanceId
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverConnectException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheIOException
     */
    public function __construct(ConfigurationOption $config, string $instanceId)
    {
        $this->setConfig($config);
        $this->instanceId = $instanceId;
        $this->IO = new DriverIO();

        if (!$this->driverCheck()) {
            throw new PhpfastcacheDriverCheckException(\sprintf(self::DRIVER_CHECK_FAILURE, $this->getDriverName()));
        }

        try {
            $this->driverConnect();
        } catch (Throwable $e) {
            throw new PhpfastcacheDriverConnectException(
                sprintf(
                    self::DRIVER_CONNECT_FAILURE,
                    $this->getDriverName(),
                    $e->getMessage(),
                    $e->getLine() ?: 'unknown line',
                    $e->getFile() ?: 'unknown file'
                )
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
        $className = self::getConfigClass();
        return new $className;
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

    /**
     * @param ExtendedCacheItemInterface $item
     * @return array
     * @throws PhpfastcacheLogicException
     */
    public function driverPreWrap(ExtendedCacheItemInterface $item): array
    {
        $wrap = [
            self::DRIVER_KEY_WRAPPER_INDEX => $item->getKey(), // Stored but not really used, allow you to quickly identify the cache key
            self::DRIVER_DATA_WRAPPER_INDEX => $item->get(),
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

        return $wrap;
    }

    /**
     * @return ConfigurationOption
     */
    abstract public function getConfig(): ConfigurationOption;

    /**
     * @param ConfigurationOption $config
     * @return static
     */
    public function setConfig(ConfigurationOption $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param array $wrapper
     * @return mixed
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
        return $wrapper[self::DRIVER_EDATE_WRAPPER_INDEX];
    }

    /**
     * @param array $wrapper
     * @return DateTime|null
     */
    public function driverUnwrapCdate(array $wrapper): ?\DateTime
    {
        return $wrapper[self::DRIVER_CDATE_WRAPPER_INDEX];
    }

    /**
     * @param array $wrapper
     * @return DateTime|null
     */
    public function driverUnwrapMdate(array $wrapper): ?\DateTime
    {
        return $wrapper[self::DRIVER_MDATE_WRAPPER_INDEX];
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
