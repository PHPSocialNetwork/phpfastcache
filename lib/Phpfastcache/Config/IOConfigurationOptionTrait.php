<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 10/02/2018
 * Time: 18:45
 */

namespace Phpfastcache\Config;

use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Util\ArrayObject;

trait IOConfigurationOptionTrait
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