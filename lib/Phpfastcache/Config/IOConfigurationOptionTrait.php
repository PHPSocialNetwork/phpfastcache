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

const SAFE_FILE_EXTENSIONS = 'txt|cache|db|pfc';

trait IOConfigurationOptionTrait
{
    /**
     * @var boolean
     */
    protected $secureFileManipulation = false;

    /**
     * @var bool
     */
    protected $htaccess = true;

    /**
     * @var string
     */
    protected $securityKey = '';

    /**
     * @var string
     */
    protected $cacheFileExtension = 'txt';

    /**
     * @return string
     */
    public function getSecurityKey(): string
    {
        return $this->securityKey;
    }

    /**
     * @param string $securityKey
     * @return Config
     */
    public function setSecurityKey(string $securityKey): self
    {
        $this->securityKey = $securityKey;

        return $this;
    }

    /**
     * @return bool
     */
    public function getHtaccess(): bool
    {
        return $this->htaccess;
    }

    /**
     * @param bool $htaccess
     * @return Config
     */
    public function setHtaccess(bool $htaccess): ConfigurationOptionInterface
    {
        $this->htaccess = $htaccess;

        return $this;
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
     * @return self
     */
    public function setSecureFileManipulation(bool $secureFileManipulation): self
    {
        $this->secureFileManipulation = $secureFileManipulation;
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
     * @return self
     * @throws PhpfastcacheInvalidConfigurationException
     */
    public function setCacheFileExtension(string $cacheFileExtension): self
    {
        /**
         * Feel free to propose your own one
         * by opening a pull request :)
         */
        $safeFileExtensions = \explode('|', SAFE_FILE_EXTENSIONS);

        if (\strpos($cacheFileExtension, '.') !== false) {
            throw new PhpfastcacheInvalidConfigurationException('cacheFileExtension cannot contain a dot "."');
        }
        if (!\in_array($cacheFileExtension, $safeFileExtensions, true)) {
            throw new PhpfastcacheInvalidConfigurationException(
                "Extension \"{$cacheFileExtension}\" is not safe, currently allowed extension names: " . \implode(', ', $safeFileExtensions)
            );
        }

        $this->cacheFileExtension = $cacheFileExtension;
        return $this;
    }
}