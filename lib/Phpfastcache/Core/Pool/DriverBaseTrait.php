<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Core\Pool;

use DateTime;
use Exception;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Exceptions\{PhpfastcacheDriverCheckException, PhpfastcacheDriverConnectException};
use ReflectionObject;


/**
 * Class DriverBaseTrait
 * @package phpFastCache\Cache
 */
trait DriverBaseTrait
{
    use ExtendedCacheItemPoolTrait;

    /**
     * @var ConfigurationOption the options
     */
    protected $config;

    /**
     * @var bool
     */
    protected $fallback = false;

    /**
     * @var mixed Instance of driver service
     */
    protected $instance;

    /**
     * @var string
     */
    protected $driverName;

    /**
     * @internal This variable is read-access only
     * @var string
     */
    protected $instanceId;

    /**
     * Driver constructor.
     * @param ConfigurationOption $config
     * @param string $instanceId
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverConnectException
     */
    public function __construct(ConfigurationOption $config, $instanceId)
    {
        $this->setConfig($config);
        $this->instanceId = $instanceId;
        $this->IO = new DriverIO();

        if (!$this->driverCheck()) {
            throw new PhpfastcacheDriverCheckException(\sprintf(self::DRIVER_CHECK_FAILURE, $this->getDriverName()));
        }

        try {
            $this->driverConnect();
        } catch (Exception $e) {
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
        if (!$this->driverName) {
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
     */
    public function driverPreWrap(ExtendedCacheItemInterface $item): array
    {
        $wrap = [
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
    public function getConfig(): ConfigurationOption
    {
        return $this->config;
    }

    /**
     * @param ConfigurationOption $config
     */
    public function setConfig(ConfigurationOption $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $wrapper
     * @return mixed
     */
    public function driverUnwrapData(array $wrapper)
    {
        return $wrapper[self::DRIVER_DATA_WRAPPER_INDEX];
    }


    /**
     * @param array $wrapper
     * @return DateTime
     */
    public function driverUnwrapEdate(array $wrapper)
    {
        return $wrapper[self::DRIVER_EDATE_WRAPPER_INDEX];
    }

    /**
     * @param array $wrapper
     * @return DateTime
     */
    public function driverUnwrapCdate(array $wrapper)
    {
        return $wrapper[self::DRIVER_CDATE_WRAPPER_INDEX];
    }

    /**
     * @param array $wrapper
     * @return DateTime
     */
    public function driverUnwrapMdate(array $wrapper)
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
    protected function decode($value)
    {
        return \unserialize((string)$value, ['allowed_classes' => true]);
    }

    /**
     * Check if phpModule or CGI
     * @return bool
     */
    protected function isPHPModule(): bool
    {
        return (\PHP_SAPI === 'apache2handler' || \strpos(\PHP_SAPI, 'handler') !== false);
    }
}
