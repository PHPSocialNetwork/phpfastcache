<?php
/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
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

    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setHost(string $host): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->host = $host;

        return $this;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setPort(int $port): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->port = $port;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->password = $password;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setTimeout(int $timeout): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->timeout = $timeout;

        return $this;
    }
}
