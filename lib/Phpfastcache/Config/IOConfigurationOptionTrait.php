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
    public function setSecurityKey(string $securityKey): ConfigurationOptionInterface
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
    public function setSecureFileManipulation(bool $secureFileManipulation): ConfigurationOptionInterface
    {
        $this->secureFileManipulation = $secureFileManipulation;
        return $this;
    }
}