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
    protected string $host = '127.0.0.1';

    protected int $port = 9042;

    protected int $timeout = 2;

    protected string $username = '';

    protected string $password = '';

    protected bool $sslEnabled = false;

    protected bool $sslVerify = false;

    protected bool $useLegacyExecutionOptions = false;

    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @throws PhpfastcacheLogicException
     */
    public function setHost(string $host): static
    {
        return $this->setProperty('host', $host);
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
     * @throws PhpfastcacheLogicException
     */
    public function setPort(int $port): static
    {
        return $this->setProperty('port', $port);
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
     * @throws PhpfastcacheLogicException
     */
    public function setTimeout(int $timeout): static
    {
        return $this->setProperty('timeout', $timeout);
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
     * @throws PhpfastcacheLogicException
     */
    public function setUsername(string $username): static
    {
        return $this->setProperty('username', $username);
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
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): static
    {
        return $this->setProperty('password', $password);
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
     * @throws PhpfastcacheLogicException
     */
    public function setSslEnabled(bool $sslEnabled): static
    {
        return $this->setProperty('sslEnabled', $sslEnabled);
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
     * @throws PhpfastcacheLogicException
     */
    public function setSslVerify(bool $sslVerify): static
    {
        return $this->setProperty('sslVerify', $sslVerify);
    }

    /**
     * @return bool
     */
    public function isUseLegacyExecutionOptions(): bool
    {
        return $this->useLegacyExecutionOptions;
    }

    /**
     * @param bool $useLegacyExecutionOptions
     * @throws PhpfastcacheLogicException
     */
    public function setUseLegacyExecutionOptions(bool $useLegacyExecutionOptions): static
    {
        return $this->setProperty('useLegacyExecutionOptions', $useLegacyExecutionOptions);
    }
}
