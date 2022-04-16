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
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

interface ConfigurationOptionInterface extends LockableConfigurationInterface
{
    /**
     * @param array<string, mixed> $parameters
     * ConfigurationOptionInterface constructor.
     */
    public function __construct(array $parameters = []);

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @param mixed $val
     * @return bool
     */
    public function isValueSerializable(mixed $val): bool;

    /**
     * @param string $optionName
     * @return bool
     */
    public function isValidOption(string $optionName): bool;

    /**
     * @return bool
     */
    public function isItemDetailedDate(): bool;

    /**
     * @param bool $itemDetailedDate
     * @return ConfigurationOption
     */
    public function setItemDetailedDate(bool $itemDetailedDate): static;

    /**
     * @return bool
     */
    public function isAutoTmpFallback(): bool;
    /**
     * @param bool $autoTmpFallback
     * @return ConfigurationOption
     */
    public function setAutoTmpFallback(bool $autoTmpFallback): static;
    /**
     * @return int
     */
    public function getDefaultTtl(): int;
    /**
     * @param int $defaultTtl
     * @return ConfigurationOption
     */
    public function setDefaultTtl(int $defaultTtl): static;
    /**
     * @return callable|string
     */
    public function getDefaultKeyHashFunction(): callable|string;
    /**
     * @param callable|string $defaultKeyHashFunction
     * @return ConfigurationOption
     * @throws  PhpfastcacheInvalidConfigurationException
     */
    public function setDefaultKeyHashFunction(callable|string $defaultKeyHashFunction): static;
    /**
     * @return callable|string
     */
    public function getDefaultFileNameHashFunction(): callable|string;
    /**
     * @param callable|string $defaultFileNameHashFunction
     * @return ConfigurationOption
     * @throws  PhpfastcacheInvalidConfigurationException
     */
    public function setDefaultFileNameHashFunction(callable|string $defaultFileNameHashFunction): static;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @param string $path
     * @return ConfigurationOption
     */
    public function setPath(string $path): static;

    /**
     * @return bool
     */
    public function isPreventCacheSlams(): bool;

    /**
     * @param bool $preventCacheSlams
     * @return ConfigurationOption
     */
    public function setPreventCacheSlams(bool $preventCacheSlams): static;

    /**
     * @return int
     */
    public function getCacheSlamsTimeout(): int;

    /**
     * @param int $cacheSlamsTimeout
     * @return ConfigurationOption
     */
    public function setCacheSlamsTimeout(int $cacheSlamsTimeout): static;
    /**
     * @return bool
     */
    public function isUseStaticItemCaching(): bool;

    /**
     * @param bool $useStaticItemCaching
     * @return ConfigurationOption
     */
    public function setUseStaticItemCaching(bool $useStaticItemCaching): static;

    /**
     * @return object
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function getSuperGlobalAccessor(): object;

    /**
     * @param ?object $superGlobalAccessor
     * @return static
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function setSuperGlobalAccessor(?object $superGlobalAccessor): static;
}
