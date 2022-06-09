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
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

class ConfigurationOption extends AbstractConfigurationOption implements ConfigurationOptionInterface
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
     * @inheritDoc
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws PhpfastcacheInvalidTypeException
     */
    public function __construct(array $parameters = [])
    {
        foreach ($parameters as $configKey => $configVal) {
            try {
                if (\property_exists($this, $configKey)) {
                    $this->{'set' . \ucfirst($configKey)}($configVal);
                } else {
                    throw new PhpfastcacheInvalidConfigurationException(
                        sprintf(
                            'Unknown configuration option name "%s" for the config class "%s". Allowed configurations options are "%s"',
                            $configKey,
                            $this::class,
                            \implode('", "', \array_keys($this->toArray())),
                        )
                    );
                }
            } catch (\TypeError $e) {
                throw new PhpfastcacheInvalidTypeException(
                    \sprintf(
                        'TypeError exception thrown while trying to set your configuration: %s',
                        $e->getMessage()
                    )
                );
            }
        }
    }

    public function toArray(): array
    {
        return \get_object_vars($this);
    }

    /**
     * @throws \ReflectionException
     */
    public function isValueSerializable(mixed $val): bool
    {
        return !\is_callable($val) && !(is_object($val) && (new \ReflectionClass($val))->isAnonymous());
    }

    /**
     * @param string $optionName
     * @return bool
     */
    public function isValidOption(string $optionName): bool
    {
        return \property_exists($this, $optionName);
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
     * @throws PhpfastcacheLogicException
     */
    public function setItemDetailedDate(bool $itemDetailedDate): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
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
     * @throws PhpfastcacheLogicException
     */
    public function setAutoTmpFallback(bool $autoTmpFallback): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
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
     * @throws PhpfastcacheLogicException
     */
    public function setDefaultTtl(int $defaultTtl): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
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
     * @throws PhpfastcacheLogicException
     */
    public function setDefaultKeyHashFunction(callable|string $defaultKeyHashFunction): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
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
     * @throws PhpfastcacheLogicException
     */
    public function setDefaultFileNameHashFunction(callable|string $defaultFileNameHashFunction): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
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
     * @throws PhpfastcacheLogicException
     */
    public function setPath(string $path): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
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
     * @throws PhpfastcacheLogicException
     */
    public function setPreventCacheSlams(bool $preventCacheSlams): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
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
     * @throws PhpfastcacheLogicException
     */
    public function setCacheSlamsTimeout(int $cacheSlamsTimeout): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
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
     * @throws PhpfastcacheLogicException
     */
    public function setUseStaticItemCaching(bool $useStaticItemCaching): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->useStaticItemCaching = $useStaticItemCaching;
        return $this;
    }

    /**
     * @return object
     */
    public function getSuperGlobalAccessor(): object
    {
        if (!isset($this->superGlobalAccessor)) {
            $this->superGlobalAccessor = $this->getDefaultSuperGlobalAccessor();
        }

        return $this->superGlobalAccessor;
    }

    /**
     * @param ?object $superGlobalAccessor
     * @return static
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function setSuperGlobalAccessor(?object $superGlobalAccessor): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
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
        return \Closure::fromCallable(static function (string $superGlobalName, ?string $keyName = null): string|int|float|array|bool|null {
            return match ($superGlobalName) {
                'SERVER' => $keyName !== null ? $_SERVER[$keyName] ?? null : $_SERVER,
                'REQUEST' => $keyName !== null ? $_REQUEST[$keyName] ?? null : $_REQUEST,
                'COOKIE' => $keyName !== null ? $_COOKIE[$keyName] ?? null : $_COOKIE,
                default => null,
            };
        });
    }
}
