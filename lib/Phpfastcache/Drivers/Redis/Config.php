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
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setPort(int $port): static
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
     *
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->password = $password;
        return $this;
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
        $this->enforceLockedProperty(__FUNCTION__);
        $this->database = $database;
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
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setTimeout(int $timeout): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->timeout = $timeout;
        return $this;
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
        $this->enforceLockedProperty(__FUNCTION__);
        $this->redisClient = $redisClient;
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
     * @throws PhpfastcacheLogicException
     * @since 7.0.2
     */
    public function setOptPrefix(string $optPrefix): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->optPrefix = trim($optPrefix);
        return $this;
    }
}
