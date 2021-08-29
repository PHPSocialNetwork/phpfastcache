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

namespace Phpfastcache\Drivers\Cookie;

use Phpfastcache\Config\ConfigurationOption;

class Config extends ConfigurationOption
{
    protected $awareOfUntrustableData = false;
    /**
     * @var int
     */
    protected $limitedMemoryByObject = 4096;

    /**
     * @return bool
     */
    public function isAwareOfUntrustableData(): bool
    {
        return $this->awareOfUntrustableData;
    }

    /**
     * @param bool $awareOfUntrustableData
     * @return Config
     */
    public function setAwareOfUntrustableData(bool $awareOfUntrustableData): Config
    {
        $this->awareOfUntrustableData = $awareOfUntrustableData;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimitedMemoryByObject(): int
    {
        return $this->limitedMemoryByObject;
    }

    /**
     * @param int $limitedMemoryByObject
     * @return ConfigurationOption
     */
    public function setLimitedMemoryByObject(int $limitedMemoryByObject): self
    {
        $this->limitedMemoryByObject = $limitedMemoryByObject;
        return $this;
    }
}
