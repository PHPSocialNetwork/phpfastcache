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

namespace Phpfastcache\Drivers\Mongodb;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

class Config extends ConfigurationOption
{
    protected string $host = '127.0.0.1';
    protected int $port = 27017;
    protected int $timeout = 3;
    protected string $username = '';
    protected string $password = '';
    protected string $collectionName = 'phpfastcache';
    protected string $databaseName = Driver::MONGODB_DEFAULT_DB_NAME;
    protected string $protocol = 'mongodb';

    /** @var array<mixed>  */
    protected array $servers = [];

    /** @var array<mixed>  */
    protected array $options = [];

    /** @var array<mixed>  */
    protected array $driverOptions = [];

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
     * @return array<mixed>
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * @param array<mixed> $servers
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setServers(array $servers): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->servers = $servers;
        return $this;
    }

    /**
     * @return string
     */
    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setCollectionName(string $collectionName): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->collectionName = $collectionName;
        return $this;
    }

    /**
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * @param string $databaseName
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setDatabaseName(string $databaseName): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->databaseName = $databaseName;
        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @see https://docs.mongodb.com/manual/reference/connection-string/#connections-connection-options
     * @param array<mixed> $options
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setOptions(array $options): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->options = $options;
        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getDriverOptions(): array
    {
        return $this->driverOptions;
    }

    /**
     * @param array<mixed> $driverOptions
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setDriverOptions(array $driverOptions): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->driverOptions = $driverOptions;
        return $this;
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setProtocol(string $protocol): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->protocol = $protocol;
        return $this;
    }
}
