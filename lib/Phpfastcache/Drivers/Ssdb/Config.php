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

namespace Phpfastcache\Drivers\Ssdb;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

class Config extends ConfigurationOption
{
    protected string $host = '127.0.0.1';
    protected int $port = 8888;
    protected string $password = '';
    protected int $timeout = 2000;
/**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setHost(string $host): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->host = $host;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setPort(int $port): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->port = $port;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->password = $password;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setTimeout(int $timeout): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->timeout = $timeout;
        return $this;
    }
}
