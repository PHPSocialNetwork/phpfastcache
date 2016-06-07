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

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;
use Redis as RedisClient;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct(array $config = [])
    {
        $this->setup($config);

        if (!$this->driverCheck()) {
            throw new phpFastCacheDriverCheckException(sprintf(self::DRIVER_CHECK_FAILURE, $this->getDriverName()));
        } else {
            $this->driverConnect();
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        return extension_loaded('Redis');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $ttl = $item->getExpirationDate()->getTimestamp() - time();

            return $this->instance->setex($item->getKey(), $ttl, $this->encode($this->driverPreWrap($item)));
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
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
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return $this->instance->del($item->getKey());
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return $this->instance->flushDB();
    }

    /**
     * @return bool
     */
    protected function driverConnect()
    {
        if ($this->instance instanceof RedisClient) {
            throw new \LogicException('Already connected to Redis server');
        } else {
            $this->instance = $this->instance ?: new RedisClient();

            $host = isset($this->config[ 'host' ]) ? $this->config[ 'host' ] : '127.0.0.1';
            $port = isset($this->config[ 'port' ]) ? (int) $this->config[ 'port' ] : '6379';
            $password = isset($this->config[ 'password' ]) ? $this->config[ 'password' ] : '';
            $database = isset($this->config[ 'database' ]) ? $this->config[ 'database' ] : '';
            $timeout = isset($this->config[ 'timeout' ]) ? $this->config[ 'timeout' ] : '';

            if (!$this->instance->connect($host, (int) $port, (int) $timeout)) {
                return false;
            } else {
                if ($password && !$this->instance->auth($password)) {
                    return false;
                }
                if ($database) {
                    $this->instance->select((int) $database);
                }

                return true;
            }
        }
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return driverStatistic
     */
    public function getStats()
    {
        // used_memory
        $info = $this->instance->info();
        $date = (new \DateTime())->setTimestamp(time() - $info[ 'uptime_in_seconds' ]);

        return (new driverStatistic())
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setRawData($info)
          ->setSize($info[ 'used_memory' ])
          ->setInfo(sprintf("The Redis daemon v%s is up since %s.\n For more information see RawData. \n Driver size includes the memory allocation size.", $info[ 'redis_version' ], $date->format(DATE_RFC2822)));
    }
}