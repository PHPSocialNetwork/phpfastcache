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

namespace Phpfastcache\Config;

use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Util\ArrayObject;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use TypeError;

/**
 * Class ConfigurationOption
 * @package Phpfastcache\Config
 */
class ConfigurationOption extends ArrayObject implements ConfigurationOptionInterface
{
    /**
     * @var bool
     */
    protected $itemDetailedDate = false;

    /**
     * @var bool
     */
    protected $autoTmpFallback = false;

    /**
     * @var int
     */
    protected $defaultTtl = 900;

    /**
     * @var string|Callable
     */
    protected $defaultKeyHashFunction = 'md5';

    /**
     * @var string|Callable
     */
    protected $defaultFileNameHashFunction = 'md5';

    /**
     * @var int
     */
    protected $defaultChmod = 0777;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var int
     */
    protected $limitedMemoryByObject = 4096;

    /**
     * @var bool
     */
    protected $compressData = false;

    /**
     * @var bool
     */
    protected $preventCacheSlams = false;

    /**
     * @var int
     */
    protected $cacheSlamsTimeout = 15;

    /**
     * @var bool
     */
    protected $useStaticItemCaching = true;

    /**
     * @param $args
     * ArrayObject constructor.
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws ReflectionException
     */
    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $array =& $this->getArray();

        /**
         * Detect unwanted keys and throw an exception.
         * No more kidding now, it's 21th century.
         */
        if (\array_diff_key($array, \get_object_vars($this))) {
            throw new PhpfastcacheInvalidConfigurationException(
                sprintf(
                    'Invalid option(s) for the config %s: %s',
                    static::class,
                    \implode(', ', \array_keys(\array_diff_key($array, \get_object_vars($this))))
                )
            );
        }

        foreach (\get_object_vars($this) as $property => $value) {
            if (\array_key_exists($property, $array)) {
                $this->$property = &$array[$property];
            } else {
                $array[$property] = &$this->$property;
            }
        }

        foreach (\get_class_methods($this) as $method) {
            if (\strpos($method, 'set') === 0) {
                $value = null;
                try {
                    /**
                     * We use property instead of getter
                     * because of is/get conditions and
                     * to allow us to retrieve the value
                     * in catch statement bloc
                     */
                    $value = $this->{\lcfirst(\substr($method, 3))};
                    $this->{$method}($value);
                } catch (TypeError $e) {
                    $typeHintGot = \is_object($value) ? \get_class($value) : \gettype($value);
                    $reflectionMethod = new ReflectionMethod($this, $method);
                    $parameter = $reflectionMethod->getParameters()[0] ?? null;
                    $typeHintExpected = ($parameter instanceof ReflectionParameter ? ($parameter->getType()->getName() === 'object' ? $parameter->getClass() : $parameter->getType(
                    )->getName()) : 'Unknown type');

                    throw new PhpfastcacheInvalidConfigurationException(
                        \sprintf(
                            'Invalid type hint found for "%s", expected "%s" got "%s"',
                            \lcfirst(\substr($method, 3)),
                            $typeHintExpected,
                            $typeHintGot
                        )
                    );
                }
            }
        }
    }

    /**
     * @param string $optionName
     * @return mixed|null
     */
    public function isValidOption(string $optionName)
    {
        return property_exists($this, $optionName);
    }

    /**
     * @return bool
     */
    public function isItemDetailedDate(): bool
    {
        return $this->itemDetailedDate;
    }

    /**
     * @param bool $itemDetailedDate
     * @return ConfigurationOption
     */
    public function setItemDetailedDate(bool $itemDetailedDate): self
    {
        $this->itemDetailedDate = $itemDetailedDate;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoTmpFallback(): bool
    {
        return $this->autoTmpFallback;
    }

    /**
     * @param bool $autoTmpFallback
     * @return ConfigurationOption
     */
    public function setAutoTmpFallback(bool $autoTmpFallback): self
    {
        $this->autoTmpFallback = $autoTmpFallback;
        return $this;
    }

    /**
     * @return int
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }

    /**
     * @param int $defaultTtl
     * @return ConfigurationOption
     */
    public function setDefaultTtl(int $defaultTtl): self
    {
        $this->defaultTtl = $defaultTtl;
        return $this;
    }

    /**
     * @return Callable|string
     */
    public function getDefaultKeyHashFunction()
    {
        return $this->defaultKeyHashFunction;
    }

    /**
     * @param Callable|string $defaultKeyHashFunction
     * @return ConfigurationOption
     * @throws  PhpfastcacheInvalidConfigurationException
     */
    public function setDefaultKeyHashFunction($defaultKeyHashFunction): self
    {
        if ($defaultKeyHashFunction && !\is_callable($defaultKeyHashFunction) && (\is_string($defaultKeyHashFunction) && !\function_exists($defaultKeyHashFunction))) {
            throw new PhpfastcacheInvalidConfigurationException('defaultKeyHashFunction must be a valid function name string');
        }
        $this->defaultKeyHashFunction = $defaultKeyHashFunction;
        return $this;
    }

    /**
     * @return Callable|string
     */
    public function getDefaultFileNameHashFunction()
    {
        return $this->defaultFileNameHashFunction;
    }

    /**
     * @param Callable|string $defaultFileNameHashFunction
     * @return ConfigurationOption
     * @throws  PhpfastcacheInvalidConfigurationException
     */
    public function setDefaultFileNameHashFunction($defaultFileNameHashFunction): self
    {
        if (!\is_callable($defaultFileNameHashFunction) && (\is_string($defaultFileNameHashFunction) && !\function_exists($defaultFileNameHashFunction))) {
            throw new PhpfastcacheInvalidConfigurationException('defaultFileNameHashFunction must be a valid function name string');
        }
        $this->defaultFileNameHashFunction = $defaultFileNameHashFunction;
        return $this;
    }

    /**
     * @return int
     */
    public function getDefaultChmod(): int
    {
        return $this->defaultChmod;
    }

    /**
     * @param int $defaultChmod
     * @return ConfigurationOption
     */
    public function setDefaultChmod(int $defaultChmod): self
    {
        $this->defaultChmod = $defaultChmod;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return ConfigurationOption
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimitedMemoryByObject(): int
    {
        return $this->limitedMemoryByObject;
    }

    /**
     * @param int $limitedMemoryByObject
     * @return ConfigurationOption
     */
    public function setLimitedMemoryByObject(int $limitedMemoryByObject): self
    {
        $this->limitedMemoryByObject = $limitedMemoryByObject;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCompressData(): bool
    {
        return $this->compressData;
    }

    /**
     * @param bool $compressData
     * @return ConfigurationOption
     */
    public function setCompressData(bool $compressData): self
    {
        $this->compressData = $compressData;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPreventCacheSlams(): bool
    {
        return $this->preventCacheSlams;
    }

    /**
     * @param bool $preventCacheSlams
     * @return ConfigurationOption
     */
    public function setPreventCacheSlams(bool $preventCacheSlams): self
    {
        $this->preventCacheSlams = $preventCacheSlams;
        return $this;
    }

    /**
     * @return int
     */
    public function getCacheSlamsTimeout(): int
    {
        return $this->cacheSlamsTimeout;
    }

    /**
     * @param int $cacheSlamsTimeout
     * @return ConfigurationOption
     */
    public function setCacheSlamsTimeout(int $cacheSlamsTimeout): self
    {
        $this->cacheSlamsTimeout = $cacheSlamsTimeout;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseStaticItemCaching(): bool
    {
        return $this->useStaticItemCaching;
    }

    /**
     * @param bool $useStaticItemCaching
     * @return ConfigurationOption
     */
    public function setUseStaticItemCaching(bool $useStaticItemCaching): self
    {
        $this->useStaticItemCaching = $useStaticItemCaching;
        return $this;
    }
}
