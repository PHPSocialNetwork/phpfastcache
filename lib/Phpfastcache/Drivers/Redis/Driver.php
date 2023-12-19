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

use DateTime;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Redis as RedisClient;

/**
 * @property RedisClient $instance
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use RedisDriverTrait, TaggableCacheItemPoolTrait {
        RedisDriverTrait::driverReadMultiple insteadof TaggableCacheItemPoolTrait;
        RedisDriverTrait::driverDeleteMultiple insteadof TaggableCacheItemPoolTrait;
    }


    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('Redis') && class_exists(RedisClient::class);
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        // used_memory
        $info = $this->instance->info();
        $date = (new DateTime())->setTimestamp(time() - $info['uptime_in_seconds']);

        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData($info)
            ->setSize((int)$info['used_memory_dataset'])
            ->setInfo(
                sprintf(
                    "The Redis daemon v%s, php-ext v%s, is up since %s.\n For more information see RawData.",
                    $info['redis_version'],
                    \phpversion("redis"),
                    $date->format(DATE_RFC2822)
                )
            );
    }

    /**
     * @return bool
     * @throws PhpfastcacheLogicException
     */
    protected function driverConnect(): bool
    {
        if (isset($this->instance) && $this->instance instanceof RedisClient) {
            throw new PhpfastcacheLogicException('Already connected to Redis server');
        }

        /**
         * In case of an user-provided
         * Redis client just return here
         */
        if ($this->getConfig()->getRedisClient() instanceof RedisClient) {
            /**
             * Unlike Predis, we can't test if we're connected
             * or not, so let's just assume that we are
             */
            $this->instance = $this->getConfig()->getRedisClient();
            return true;
        }

        $this->instance = $this->instance ?? new RedisClient();

        /**
         * If path is provided we consider it as a UNIX Socket
         */
        if ($this->getConfig()->getPath()) {
            $isConnected = $this->instance->connect($this->getConfig()->getPath());
        } else {
            $isConnected = $this->instance->connect($this->getConfig()->getHost(), $this->getConfig()->getPort(), $this->getConfig()->getTimeout());
        }

        if (!$isConnected && $this->getConfig()->getPath()) {
            return false;
        }

        if ($this->getConfig()->getOptPrefix()) {
            $this->instance->setOption(RedisClient::OPT_PREFIX, $this->getConfig()->getOptPrefix());
        }

        if ($this->getConfig()->getPassword() && !$this->instance->auth($this->getConfig()->getPassword())) {
            return false;
        }

        if ($this->getConfig()->getDatabase() !== null) {
            $this->instance->select($this->getConfig()->getDatabase());
        }
        return true;
    }

    /**
     * @return array<int, string>
     * @throws \RedisException
     */
    protected function driverReadAllKeys(string $pattern = '*'): iterable
    {
        $i = -1;
        $keys = $this->instance->scan($i, $pattern === '' ? '*' : $pattern, ExtendedCacheItemPoolInterface::MAX_ALL_KEYS_COUNT);
        if (is_iterable($keys)) {
            return $keys;
        } else {
            return [];
        }
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->instance->flushDB();
    }
}
