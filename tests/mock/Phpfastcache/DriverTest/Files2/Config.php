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

namespace Phpfastcache\DriverTest\Files2;

use Phpfastcache\Drivers\Files\Config as FilesConfig;

/**
 * Class Config
 * @package Phpfastcache\DriverTest\Files2
 */
class Config extends FilesConfig
{
    /**
     * @var bool
     */
    protected $customOption = true;

    /**
     * @return bool
     */
    public function isCustomOption(): bool
    {
        return $this->customOption;
    }

    /**
     * @param bool $customOption
     * @return Config
     */
    public function setCustomOption(bool $customOption): Config
    {
        $this->customOption = $customOption;
        return $this;
    }
}
