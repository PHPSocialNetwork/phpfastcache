<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 12/02/2018
 * Time: 23:10
 */

namespace Phpfastcache\Drivers\Files;

use Phpfastcache\Config\ConfigurationOption;

class Config extends ConfigurationOption
{
    /**
     * @var boolean
     */
    protected $secureFileManipulation = false;

    /**
     * @var string
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
    public function setSecurityKey(string $securityKey): Config
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
    public function setHtaccess(bool $htaccess): self
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
}