<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

declare(strict_types=1);

namespace Phpfastcache\Drivers\Predis;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;

class Config extends ConfigurationOption
{
    /**
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * @var int
     */
    protected $port = 6379;

    /**
     * @var string
     */
    protected $password = '';

    /**
     * @var int
     */
    protected $database = 0;

    /**
     * @var \Predis\Client
     */
    protected $predisClient;

    /**
     * @var string
     */
    protected $optPrefix = '';

    /**
     * @var int
     */
    protected $timeout = 5;

    /**
     * @var bool
     */
    protected $persistent = false;

    /**
     * @var string
     */
    protected $scheme = 'unix';

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
     */
    public function setHost(string $host): self
    {
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
     */
    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param null $password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
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
     */
    public function setDatabase(int $database): self
    {
        $this->database = $database;
        return $this;
    }

    /**
     * @return array
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
     * @return \Predis\Client|null
     */
    public function getPredisClient()
    {
        return $this->predisClient;
    }

    /**
     * @param \Predis\Client $predisClient |null
     * @return Config
     */
    public function setPredisClient(\Predis\Client $predisClient = null): Config
    {
        $this->predisClient = $predisClient;
        return $this;
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
     * @since 7.0.2
     */
    public function setOptPrefix(string $optPrefix): Config
    {
        $this->optPrefix = trim($optPrefix);
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
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
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
     */
    public function setPersistent(bool $persistent): Config
    {
        $this->persistent = $persistent;
        return $this;
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
     */
    public function setScheme(string $scheme): Config
    {
        if(!\in_array($scheme, ['unix', 'tls'], true)){
            throw new PhpfastcacheInvalidConfigurationException('Invalid scheme: ' . $scheme);
        }
        $this->scheme = $scheme;
        return $this;
    }
}