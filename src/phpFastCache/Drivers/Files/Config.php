<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 12/02/2018
 * Time: 23:10
 */

namespace phpFastCache\Drivers\Files;

use phpFastCache\Config\ConfigurationOption;

class Config extends ConfigurationOption
{
    /**
     * @var boolean
     */
    protected $secureFileManipulation = false;

    /**
     * @var string
     */
    protected $htaccess = 'Auto';

    /**
     * @return string
     */
    public function getHtaccess(): string
    {
        return $this->htaccess;
    }

    /**
     * @param string $htaccess
     * @return Config
     */
    public function setHtaccess(string $htaccess): self
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