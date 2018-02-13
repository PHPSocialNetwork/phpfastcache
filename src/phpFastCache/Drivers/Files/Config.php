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