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
use Redis as RedisClient;

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
     * @var null|string
     */
    protected $password = '';

    /**
     * @var null|int
     */
    protected $database = 0;

    /**
     * @var int
     */
    protected $timeout = 5;

    /**
     * @var RedisClient
     */
    protected $redisClient;

    /**
     * @var string
     */
    protected $optPrefix = '';

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
     */
    public function setHost(string $host): static
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
     * @return self
     */
    public function setPort(int $port): static
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     *
     * @return self
     */
    public function setPassword(string $password = null): static
    {
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
     * @return self
     */
    public function setDatabase(int $database = null): static
    {
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
     * @return self
     */
    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return RedisClient|null
     */
    public function getRedisClient()
    {
        return $this->redisClient;
    }

    /**
     * @param RedisClient $predisClient |null
     * @return Config
     */
    public function setRedisClient(RedisClient $redisClient = null): Config
    {
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
     * @since 7.0.2
     */
    public function setOptPrefix(string $optPrefix): Config
    {
        $this->optPrefix = trim($optPrefix);
        return $this;
    }
}
