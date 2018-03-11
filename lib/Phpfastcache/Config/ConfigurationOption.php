<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 10/02/2018
 * Time: 18:45
 */

namespace Phpfastcache\Config;

use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Util\ArrayObject;

class ConfigurationOption extends ArrayObject
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
     * @var bool
     * @deprecated Do not use this option anymore
     */
    protected $ignoreSymfonyNotice = false;

    /**
     * @var int
     */
    protected $defaultTtl = 900;

    /**
     * @var string|Callable
     */
    protected $defaultKeyHashFunction = 'md5';

    /**
     * @var int
     */
    protected $defaultChmod = 0777;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var string
     */
    protected $fallback = '';

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
     * @var string
     */
    protected $cacheFileExtension = 'txt';

    /**
     * @param $args
     * ArrayObject constructor.
     */
    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $array =& $this->getArray();

        /**
         * Detect unwanted keys and throw an exception.
         * No more kidding now, it's 21th century.
         */
        if(array_diff_key($array, get_object_vars($this))){
            throw new PhpfastcacheInvalidConfigurationException(sprintf(
              'Invalid option(s) for the config %s: %s',
              static::class,
              implode(', ',  array_keys(array_diff_key($array, get_object_vars($this))))
            ));
        }

        foreach (get_object_vars($this) as $property => $value) {

            if(array_key_exists($property, $array)){
                $this->$property = &$array[ $property ];
            }else{
                $array[ $property ] = &$this->$property;
            }
        }

        foreach (get_class_methods($this) as $method) {
            if(strpos($method, 'set') === 0){
                $value = null;
                try{
                    /**
                     * We use property instead of getter
                     * because of is/get conditions and
                     * to allow us to retrieve the value
                     * in catch statement bloc
                     */
                    $value = $this->{lcfirst(substr($method, 3))};
                    $this->{$method}($value);
                }catch(\TypeError $e){
                    $typeHintGot = \is_object($value) ? \get_class($value) : \gettype($value);
                    $reflectionMethod = new \ReflectionMethod($this, $method);
                    $parameter = $reflectionMethod->getParameters()[0] ?? null;
                    $typeHintExpected = ($parameter instanceof \ReflectionParameter ? ($parameter->getType() === 'object' ? $parameter->getClass() : $parameter->getType()) : 'Unknown type');

                    throw new PhpfastcacheInvalidConfigurationException(sprintf(
                      'Invalid type hint found for "%s", expected "%s" got "%s"',
                      lcfirst(substr($method, 3)),
                      $typeHintExpected,
                      $typeHintGot
                    ));
                }
            }
        }
    }

    /**
     * @param string $optionName
     * @return mixed|null
     */
    public function getOption(string $optionName)
    {
        return $this->$optionName ?? null;
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
     * @return bool
     */
    public function isIgnoreSymfonyNotice(): bool
    {
        return $this->ignoreSymfonyNotice;
    }

    /**
     * @param bool $ignoreSymfonyNotice
     * @return ConfigurationOption
     */
    public function setIgnoreSymfonyNotice(bool $ignoreSymfonyNotice): self
    {
        $this->ignoreSymfonyNotice = $ignoreSymfonyNotice;
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
    public function setDefaultKeyHashFunction($defaultKeyHashFunction)
    {
        if (!\function_exists($defaultKeyHashFunction) || !\is_callable($defaultKeyHashFunction)) {
            throw new PhpfastcacheInvalidConfigurationException('defaultKeyHashFunction must be a valid function name string');
        }
        $this->defaultKeyHashFunction = $defaultKeyHashFunction;
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
     * @return bool|string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * @param string $fallback
     * @return ConfigurationOption
     */
    public function setFallback(string $fallback): self
    {
        $this->fallback = $fallback;
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
     * @return string
     */
    public function getCacheFileExtension(): string
    {
        return $this->cacheFileExtension;
    }

    /**
     * @param string $cacheFileExtension
     * @return ConfigurationOption
     * @throws PhpfastcacheInvalidConfigurationException
     */
    public function setCacheFileExtension(string $cacheFileExtension): self
    {
        /**
         * Feel free to propose your own one
         * by opening a pull request :)
         */
        static $safeFileExtensions = [
          'txt',
          'cache',
          'db',
          'pfc',
        ];

        if (\strpos($cacheFileExtension, '.') !== false) {
            throw new PhpfastcacheInvalidConfigurationException('cacheFileExtension cannot contain a dot "."');
        }
        if (!\in_array($cacheFileExtension, $safeFileExtensions, true)) {
            throw new PhpfastcacheInvalidConfigurationException(
              "{$cacheFileExtension} is not a safe extension, currently allowed extension: " . \implode(', ', $safeFileExtensions)
            );
        }

        $this->cacheFileExtension = $cacheFileExtension;
        return $this;
    }
}