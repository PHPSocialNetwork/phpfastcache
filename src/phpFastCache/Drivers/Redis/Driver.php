<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace phpFastCache\Drivers\Redis;

use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheLogicException;
use phpFastCache\Util\ArrayObject;
use Psr\Cache\CacheItemInterface;
use Redis as RedisClient;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property RedisClient $instance Instance of driver service
 */
class Driver implements ExtendedCacheItemPoolInterface
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
     * @return bool
     * @throws phpFastCacheLogicException
     */
    protected function driverConnect(): bool
    {
        if ($this->instance instanceof RedisClient) {
            throw new phpFastCacheLogicException('Already connected to Redis server');
        } else {
            $clientConfig = $this->getConfig();
            $this->instance = $this->instance ?: new RedisClient();

            if (!$this->instance->connect($clientConfig['host'], (int) $clientConfig['port'], (float) $clientConfig['timeout'])) {
                return false;
            } else {
                if ($clientConfig['password'] && !$this->instance->auth($clientConfig['password'])) {
                    return false;
                }
                if ($clientConfig['database']) {
                    $this->instance->select((int) $clientConfig['database']);
                }

                return true;
            }
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $val = $this->instance->get($item->getKey());
        if ($val == false) {
            return null;
        } else {
            return $this->decode($val);
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheInvalidArgumentException
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
            if($ttl <= 0){
                return $this->instance->expire($item->getKey(), 0);
            }else{
                return $this->instance->setex($item->getKey(), $ttl, $this->encode($this->driverPreWrap($item)));
            }
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return $this->instance->del($item->getKey());
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->instance->flushDB();
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        // used_memory
        $info = $this->instance->info();
        $date = (new \DateTime())->setTimestamp(time() - $info[ 'uptime_in_seconds' ]);

        return (new DriverStatistic())
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setRawData($info)
          ->setSize($info[ 'used_memory' ])
          ->setInfo(sprintf("The Redis daemon v%s is up since %s.\n For more information see RawData. \n Driver size includes the memory allocation size.",
            $info[ 'redis_version' ], $date->format(DATE_RFC2822)));
    }

    /**
     * @return ArrayObject
     */
    public function getDefaultConfig(): ArrayObject
    {
        $defaultConfig = new ArrayObject();

        $defaultConfig['host'] = '127.0.0.1';
        $defaultConfig['port'] = 6379;
        $defaultConfig['password'] = null;
        $defaultConfig['database'] = 0;
        $defaultConfig['timeout'] = 5;

        return $defaultConfig;
    }
}