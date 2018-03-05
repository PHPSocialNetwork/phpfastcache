<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 12/02/2018
 * Time: 23:10
 */

namespace Phpfastcache\Drivers\Leveldb;

use Phpfastcache\Config\ConfigurationOption;

class Config extends ConfigurationOption
{
    /**
     * @var string
     */
    protected $htaccess = true;

    /**
     * @return bool
     */
    public function getHtaccess(): bool
    {
        return $this->htaccess;
    }

    /**
     * @param bool $htaccess
     * @return self
     */
    public function setHtaccess(bool $htaccess): self
    {
        $this->htaccess = $htaccess;
        return $this;
    }
}