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

use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * @todo: As of V10, imports cache slams properties.
 */
class IOConfigurationOption extends ConfigurationOption
{
    protected bool $secureFileManipulation = false;

    protected string $securityKey = '';

    protected string $cacheFileExtension = 'txt';

    protected int $defaultChmod = 0777;

    /**
     * @return string
     */
    public function getSecurityKey(): string
    {
        return $this->securityKey;
    }

    /**
     * @param string $securityKey
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setSecurityKey(string $securityKey): static
    {
        return $this->setProperty('securityKey', $securityKey);
    }

    /**
     * @return bool
     */
    public function isSecureFileManipulation(): bool
    {
        return $this->secureFileManipulation;
    }

    /**
     * @param bool $secureFileManipulation
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setSecureFileManipulation(bool $secureFileManipulation): static
    {
        return $this->setProperty('secureFileManipulation', $secureFileManipulation);
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
     * @return static
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws PhpfastcacheLogicException
     */
    public function setCacheFileExtension(string $cacheFileExtension): static
    {
        $safeFileExtensions = \explode('|', IOConfigurationOptionInterface::SAFE_FILE_EXTENSIONS);

        if (\str_contains($cacheFileExtension, '.')) {
            throw new PhpfastcacheInvalidConfigurationException('cacheFileExtension cannot contain a dot "."');
        }
        if (!\in_array($cacheFileExtension, $safeFileExtensions, true)) {
            throw new PhpfastcacheInvalidConfigurationException(
                "Extension \"$cacheFileExtension\" is unsafe, currently allowed extension names: " . \implode(', ', $safeFileExtensions)
            );
        }

        return $this->setProperty('cacheFileExtension', $cacheFileExtension);
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
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setDefaultChmod(int $defaultChmod): static
    {
        return $this->setProperty('defaultChmod', $defaultChmod);
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
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setPreventCacheSlams(bool $preventCacheSlams): static
    {
        return $this->setProperty('preventCacheSlams', $preventCacheSlams);
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
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setCacheSlamsTimeout(int $cacheSlamsTimeout): static
    {
        return $this->setProperty('cacheSlamsTimeout', $cacheSlamsTimeout);
    }
}
