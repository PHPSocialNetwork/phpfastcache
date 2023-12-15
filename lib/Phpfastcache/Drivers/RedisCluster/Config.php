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

namespace Phpfastcache\Drivers\RedisCluster;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use RedisCluster as RedisClusterClient;

class Config extends ConfigurationOption
{
    protected array $clusters = [];
    protected string $password = '';
    protected int $timeout = 5;
    protected int $readTimeout = 5;
    protected ?RedisClusterClient $redisClusterClient = null;
    protected string $optPrefix = '';
    protected ?int $slaveFailover = null;

    /**
     * @return array
     */
    public function getClusters(): array
    {
        return $this->clusters;
    }

    /**
     * @param string $cluster
     * @return Config
     */
    public function addCluster(string $cluster): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusters[] = $cluster;
        return $this;
    }

    /**
     * @param string[] $clusters
     * @return Config
     */
    public function setClusters(string ...$clusters): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        if (count($clusters) === 1 && is_array($clusters[0])) {
            $this->clusters = $clusters[0];
        } else {
            $this->clusters = $clusters;
        }
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
     * @return int
     */
    public function getReadTimeout(): int
    {
        return $this->readTimeout;
    }

    /**
     * @param int $readTimeout
     * @return static
     * @throws PhpfastcacheLogicException
     */
    public function setReadTimeout(int $readTimeout): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->readTimeout = $readTimeout;
        return $this;
    }


    /**
     * @return RedisClusterClient|null
     */
    public function getRedisClusterClient(): ?RedisClusterClient
    {
        return $this->redisClusterClient;
    }

    /**
     * @param RedisClusterClient|null $redisClusterClient
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setRedisClusterClient(?RedisClusterClient $redisClusterClient): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->redisClusterClient = $redisClusterClient;
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
    public function setOptPrefix(string $optPrefix): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->optPrefix = trim($optPrefix);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSlaveFailover(): ?int
    {
        return $this->slaveFailover;
    }

    /**
     * @param int|null $slaveFailover
     * @return Config
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setSlaveFailover(?int $slaveFailover): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        if (
            $slaveFailover !== null && !in_array($slaveFailover, [
            RedisClusterClient::FAILOVER_NONE,
            RedisClusterClient::FAILOVER_ERROR,
            RedisClusterClient::FAILOVER_DISTRIBUTE,
            RedisClusterClient::FAILOVER_DISTRIBUTE_SLAVES,
            ])
        ) {
            throw new PhpfastcacheInvalidArgumentException('Invalid Slave Failover option: ' . $slaveFailover);
        }
        $this->slaveFailover = $slaveFailover;
        return $this;
    }
}
