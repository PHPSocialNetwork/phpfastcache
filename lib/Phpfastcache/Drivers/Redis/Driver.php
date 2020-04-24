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

namespace Phpfastcache\Drivers\Redis;

use DateTime;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException};
use Psr\Cache\CacheItemInterface;
use Redis as RedisClient;


/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
{
    use DriverBaseTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('Redis');
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
            ->setSize((int)$info['used_memory'])
            ->setInfo(
                sprintf(
                    "The Redis daemon v%s is up since %s.\n For more information see RawData. \n Driver size includes the memory allocation size.",
                    $info['redis_version'],
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
        if ($this->instance instanceof RedisClient) {
            throw new PhpfastcacheLogicException('Already connected to Redis server');
        }

        /**
         * In case of an user-provided
         * Redis client just return here
         */
        if ($this->getConfig()->getRedisClient() instanceof RedisClient) {
            /**
             * Unlike Predis, we can't test if we're are connected
             * or not, so let's just assume that we are
             */
            $this->instance = $this->getConfig()->getRedisClient();
            return true;
        }

        $this->instance = $this->instance ?: new RedisClient();

        /**
         * If path is provided we consider it as an UNIX Socket
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
     * @param CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $val = $this->instance->get($item->getKey());
        if ($val == false) {
            return null;
        }

        return $this->decode($val);
    }

    /**
     * @param CacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $ttl = $item->getExpirationDate()->getTimestamp() - time();

            /**
             * @see https://redis.io/commands/setex
             * @see https://redis.io/commands/expire
             */
            if ($ttl <= 0) {
                return $this->instance->expire($item->getKey(), 0);
            }

            return $this->instance->setex($item->getKey(), $ttl, $this->encode($this->driverPreWrap($item)));
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return (bool)$this->instance->del($item->getKey());
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->instance->flushDB();
    }
}
