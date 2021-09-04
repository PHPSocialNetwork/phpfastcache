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

namespace Phpfastcache\Config;

use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Util\ArrayObject;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use TypeError;

class ConfigurationOption extends ArrayObject implements ConfigurationOptionInterface
{
    protected bool $itemDetailedDate = false;

    protected bool $autoTmpFallback = false;

    protected int $defaultTtl = 900;

    /**
     * @var string|callable
     */
    protected mixed $defaultKeyHashFunction = 'md5';

    /**
     * @var string|callable
     */
    protected mixed $defaultFileNameHashFunction = 'md5';

    protected string $path = '';

    protected bool $preventCacheSlams = false;

    protected int $cacheSlamsTimeout = 15;

    protected bool $useStaticItemCaching = true;

    protected ?object $superGlobalAccessor = null;

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
            try {
                if (\array_key_exists($property, $array)) {
                    $this->$property = &$array[$property];
                } else {
                    $array[$property] = &$this->$property;
                }
            } catch (\TypeError $e) {
                throw new PhpfastcacheInvalidConfigurationException(
                    \sprintf(
                        'TypeError exception thrown while trying to set your configuration: %s',
                        $e->getMessage()
                    )
                );
            }
        }

        foreach (\get_class_methods($this) as $method) {
            if (str_starts_with($method, 'set')) {
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
                } catch (TypeError) {
                    $typeHintGot = \get_debug_type($value);
                    $reflectionMethod = new ReflectionMethod($this, $method);
                    $parameter = $reflectionMethod->getParameters()[0] ?? null;
                    $typeHintExpected = 'Unknown type';

                    if ($parameter instanceof ReflectionParameter && $parameter->getType() instanceof ReflectionNamedType) {
                        $typeHintExpected = ($parameter->getType()->getName() === 'object' ? $parameter->getType() : $parameter->getType()->getName());
                    }

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
     * @return bool
     */
    public function isValidOption(string $optionName): bool
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
    public function setItemDetailedDate(bool $itemDetailedDate): static
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
    public function setAutoTmpFallback(bool $autoTmpFallback): static
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
    public function setDefaultTtl(int $defaultTtl): static
    {
        $this->defaultTtl = $defaultTtl;
        return $this;
    }

    /**
     * @return callable|string
     */
    public function getDefaultKeyHashFunction(): callable|string
    {
        return $this->defaultKeyHashFunction;
    }

    /**
     * @param callable|string $defaultKeyHashFunction
     * @return ConfigurationOption
     * @throws  PhpfastcacheInvalidConfigurationException
     */
    public function setDefaultKeyHashFunction(callable|string $defaultKeyHashFunction): static
    {
        if ($defaultKeyHashFunction && !\is_callable($defaultKeyHashFunction) && (\is_string($defaultKeyHashFunction) && !\function_exists($defaultKeyHashFunction))) {
            throw new PhpfastcacheInvalidConfigurationException('defaultKeyHashFunction must be a valid function name string');
        }
        $this->defaultKeyHashFunction = $defaultKeyHashFunction;
        return $this;
    }

    /**
     * @return callable|string
     */
    public function getDefaultFileNameHashFunction(): callable|string
    {
        return $this->defaultFileNameHashFunction;
    }

    /**
     * @param callable|string $defaultFileNameHashFunction
     * @return ConfigurationOption
     * @throws  PhpfastcacheInvalidConfigurationException
     */
    public function setDefaultFileNameHashFunction(callable|string $defaultFileNameHashFunction): static
    {
        if (!\is_callable($defaultFileNameHashFunction) && (\is_string($defaultFileNameHashFunction) && !\function_exists($defaultFileNameHashFunction))) {
            throw new PhpfastcacheInvalidConfigurationException('defaultFileNameHashFunction must be a valid function name string');
        }
        $this->defaultFileNameHashFunction = $defaultFileNameHashFunction;
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
    public function setPath(string $path): static
    {
        $this->path = $path;
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
    public function setPreventCacheSlams(bool $preventCacheSlams): static
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
    public function setCacheSlamsTimeout(int $cacheSlamsTimeout): static
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
    public function setUseStaticItemCaching(bool $useStaticItemCaching): static
    {
        $this->useStaticItemCaching = $useStaticItemCaching;
        return $this;
    }


    /**
     * @return object
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function getSuperGlobalAccessor(): object
    {
        if (!isset($this->superGlobalAccessor)) {
            $this->setSuperGlobalAccessor($this->getDefaultSuperGlobalAccessor());
        }
        return $this->superGlobalAccessor;
    }

    /**
     * @param ?object $superGlobalAccessor
     * @return static
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setSuperGlobalAccessor(?object $superGlobalAccessor): static
    {
        /**
         *  Symfony's implementation for users that want a good control of their code:
         *
         *  $config['superGlobalAccessor'] = \Closure::fromCallable(static function(string $superGlobalName, string $keyName) use ($request) {
         *      return match ($superGlobalName) {
         *          'SERVER' => $request->server->get($keyName),
         *          'REQUEST' => $request->request->get($keyName),
         *      };
         *  });
         */

        if ($superGlobalAccessor === null) {
            $this->superGlobalAccessor = $this->getDefaultSuperGlobalAccessor();
        } elseif (!\is_callable($superGlobalAccessor)) {
            throw new PhpfastcacheInvalidArgumentException('The "superGlobalAccessor" callback must be callable using "__invoke" or \Closure implementation');
        } else {
            $this->superGlobalAccessor = $superGlobalAccessor;
        }

        return $this;
    }

    /**
     * @return \Closure
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getDefaultSuperGlobalAccessor(): \Closure
    {
        return \Closure::fromCallable(static function (string $superGlobalName, ?string $keyName = null) {
            return match ($superGlobalName) {
                'SERVER' => $keyName !== null ? $_SERVER[$keyName] ?? null : $_SERVER,
                'REQUEST' => $keyName !== null ? $_REQUEST[$keyName] ?? null : $_REQUEST,
                'COOKIE' => $keyName !== null ? $_COOKIE[$keyName] ?? null : $_COOKIE,
            };
        });
    }
}
