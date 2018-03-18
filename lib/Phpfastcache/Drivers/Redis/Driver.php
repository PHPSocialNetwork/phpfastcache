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
declare(strict_types=1);

namespace Phpfastcache\Drivers\Redis;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\{
  DriverBaseTrait, ExtendedCacheItemPoolInterface
};
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{
  PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException
};
use Phpfastcache\Util\ArrayObject;
use Psr\Cache\CacheItemInterface;
use Redis as RedisClient;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \extension_loaded('Redis');
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

        $this->instance = $this->instance ?: new RedisClient();

        /**
         * If path is provided we consider it as an UNIX Socket
         */
        if ($this->config->getOption('path')) {
            $isConnected = $this->instance->connect($this->config->getOption('path'));
        } else {
            $isConnected = $this->instance->connect($this->config->getOption('host'), $this->config->getOption('port'), (int)$this->config->getOption('timeout'));
        }

        if (!$isConnected && $this->config->getOption('path')) {
            return false;
        } else if (!$this->config->getOption('path')) {
            if ($this->config->getOption('password') && !$this->instance->auth($this->config->getOption('password'))) {
                return false;
            }
        }

        if ($this->config->getOption('database') !== null) {
            $this->instance->select($this->config->getOption('database'));
        }
        return true;
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
        }

        return $this->decode($val);
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $ttl = $item->getExpirationDate()->getTimestamp() - \time();

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
     * @param \Psr\Cache\CacheItemInterface $item
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
        $date = (new \DateTime())->setTimestamp(\time() - $info[ 'uptime_in_seconds' ]);

        return (new DriverStatistic())
          ->setData(\implode(', ', \array_keys($this->itemInstances)))
          ->setRawData($info)
          ->setSize((int)$info[ 'used_memory' ])
          ->setInfo(\sprintf("The Redis daemon v%s is up since %s.\n For more information see RawData. \n Driver size includes the memory allocation size.",
            $info[ 'redis_version' ], $date->format(DATE_RFC2822)));
    }
}