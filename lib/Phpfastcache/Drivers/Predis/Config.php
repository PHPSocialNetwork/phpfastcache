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

namespace Phpfastcache\Drivers\Predis;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Predis\Client;

class Config extends ConfigurationOption
{
    protected string $host = '127.0.0.1';
    protected int $port = 6379;
    protected string $password = '';
    protected int $database = 0;
    protected ?Client $predisClient = null;
    protected string $optPrefix = '';
    protected int $timeout = 5;
    protected bool $persistent = false;
    protected string $scheme = 'unix';
/**
     * @return array<string, mixed>
     */
    public function getPredisConfigArray(): array
    {
        return [
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'password' => $this->getPassword() ?: null,
            'database' => $this->getDatabase(),
            'timeout' => $this->getTimeout(),
        ];
    }

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
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setPort(int $port): static
    {
        return $this->setProperty('port', $port);
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
        return $this->setProperty('password', $password);
    }

    /**
     * @return int
     */
    public function getDatabase(): int
    {
        return $this->database;
    }

    /**
     * @param int $database
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setDatabase(int $database): static
    {
        return $this->setProperty('database', $database);
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
        return $this->setProperty('timeout', $timeout);
    }

    /**
     * @return Client|null
     */
    public function getPredisClient(): ?Client
    {
        return $this->predisClient;
    }

    /**
     * @param Client|null $predisClient
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setPredisClient(?Client $predisClient = null): Config
    {
        return $this->setProperty('predisClient', $predisClient);
    }

    /**
     * @return string
     * @since 7.0.2
     */
    public function getOptPrefix(): string
    {
        return $this->optPrefix;
    }

    /**
     * @param string $optPrefix
     * @return Config
     * @throws PhpfastcacheLogicException
     * @since 7.0.2
     */
    public function setOptPrefix(string $optPrefix): Config
    {
        return $this->setProperty('optPrefix', trim($optPrefix));
    }

    /**
     * @return bool
     */
    public function isPersistent(): bool
    {
        return $this->persistent;
    }

    /**
     * @param bool $persistent
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setPersistent(bool $persistent): Config
    {
        return $this->setProperty('persistent', $persistent);
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     * @return Config
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws PhpfastcacheLogicException
     */
    public function setScheme(string $scheme): Config
    {
        if (!in_array($scheme, ['unix', 'tls'], true)) {
            throw new PhpfastcacheInvalidConfigurationException('Invalid scheme: ' . $scheme);
        }
        return $this->setProperty('scheme', $scheme);
    }
}
