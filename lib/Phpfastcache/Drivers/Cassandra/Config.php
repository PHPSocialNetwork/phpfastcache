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

namespace Phpfastcache\Drivers\Cassandra;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

class Config extends ConfigurationOption
{
    /**
     * @var string
     */
    protected string $host = '127.0.0.1';
    /**
     * @var int
     */
    protected int $port = 9042;
    /**
     * @var int
     */
    protected int $timeout = 2;
    /**
     * @var string
     */
    protected string $username = '';
    /**
     * @var string
     */
    protected string $password = '';
    /**
     * @var bool
     */
    protected bool $sslEnabled = false;
    /**
     * @var bool
     */
    protected bool $sslVerify = false;

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setHost(string $host): static
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
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setPort(int $port): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->port = $port;
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
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setTimeout(int $timeout): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setUsername(string $username): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->username = $username;
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
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->password = $password;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSslEnabled(): bool
    {
        return $this->sslEnabled;
    }

    /**
     * @param bool $sslEnabled
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setSslEnabled(bool $sslEnabled): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->sslEnabled = $sslEnabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSslVerify(): bool
    {
        return $this->sslVerify;
    }

    /**
     * @param bool $sslVerify
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setSslVerify(bool $sslVerify): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->sslVerify = $sslVerify;
        return $this;
    }
}
