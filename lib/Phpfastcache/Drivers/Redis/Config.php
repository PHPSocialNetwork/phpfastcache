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

namespace Phpfastcache\Drivers\Redis;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Redis as RedisClient;

class Config extends ConfigurationOption
{
    protected string $host = '127.0.0.1';
    protected int $port = 6379;
    protected string $password = '';
    protected int $database = 0;
    protected int $timeout = 5;
    protected ?RedisClient $redisClient = null;
    protected string $optPrefix = '';
/**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return static
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
     * @return static
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
     *
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): static
    {
        return $this->setProperty('password', $password);
    }

    /**
     * @return null|int
     */
    public function getDatabase(): ?int
    {
        return $this->database;
    }

    /**
     * @param int|null $database
     *
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setDatabase(int $database = null): static
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
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setTimeout(int $timeout): static
    {
        return $this->setProperty('timeout', $timeout);
    }

    /**
     * @return RedisClient|null
     */
    public function getRedisClient(): ?RedisClient
    {
        return $this->redisClient;
    }

    /**
     * @param RedisClient|null $redisClient
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setRedisClient(?RedisClient $redisClient): Config
    {
        return $this->setProperty('redisClient', $redisClient);
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
}
