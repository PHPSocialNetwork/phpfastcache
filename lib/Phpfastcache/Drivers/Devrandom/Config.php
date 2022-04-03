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

namespace Phpfastcache\Drivers\Devrandom;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

class Config extends ConfigurationOption
{
    protected int $dataLength = 16;
    protected int $chanceOfRetrieval = 50;
/**
     * @return int
     */
    public function getDataLength(): int
    {
        return $this->dataLength;
    }

    /**
     * @param int $dataLength
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setDataLength(int $dataLength): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->dataLength = $dataLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getChanceOfRetrieval(): int
    {
        return $this->chanceOfRetrieval;
    }

    /**
     * @param int $chanceOfRetrieval
     * @return Config
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function setChanceOfRetrieval(int $chanceOfRetrieval): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        if ($chanceOfRetrieval < 0 || $chanceOfRetrieval > 100) {
            throw new PhpfastcacheInvalidArgumentException('Chance of retrieval must be between 0 and 100');
        }
        $this->chanceOfRetrieval = $chanceOfRetrieval;
        return $this;
    }
}
