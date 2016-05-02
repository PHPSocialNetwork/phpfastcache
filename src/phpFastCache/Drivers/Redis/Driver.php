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
    use StandardPsr6StructureTrait;

    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct(array $config = [])
    {
        $this->setup($config);

        if (!$this->driverCheck()) {
            throw new phpFastCacheDriverCheckException(sprintf(self::DRIVER_CHECK_FAILURE, 'Redis'));
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
    public function driverWrite(CacheItemInterface $item)
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
     * @param $key
     * @return mixed
     */
    public function driverRead($key)
    {
        $x = $this->instance->get($key);
        if ($x == false) {
            return null;
        } else {
            return $this->decode($x);
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function driverDelete(CacheItemInterface $item)
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
    public function driverClear()
    {
        return $this->instance->flushDB();
    }

    /**
     * @return bool
     */
    public function driverConnect()
    {
        if($this->instance instanceof RedisClient)
        {
            throw new \LogicException('Already connected to Redis server');
        }
        else
        {
            $this->instance = $this->instance ?: new RedisClient();

            $server = isset($this->config[ 'redis' ]) ? $this->config[ 'redis' ] : [
              'host' => '127.0.0.1',
              'port' => '6379',
              'password' => '',
              'database' => '',
              'timeout' => '1',
            ];

            $host = $server[ 'host' ];
            $port = isset($server[ 'port' ]) ? (int)$server[ 'port' ] : '';
            $password = isset($server[ 'password' ]) ? $server[ 'password' ] : '';
            $database = isset($server[ 'database' ]) ? $server[ 'database' ] : '';
            $timeout = isset($server[ 'timeout' ]) ? $server[ 'timeout' ] : '';

            if (!$this->instance->connect($host, (int)$port, (int)$timeout)) {
                return false;
            } else {
                if ($password && !$this->instance->auth($password)) {
                    return false;
                }
                if ($database) {
                    $this->instance->select((int)$database);
                }

                return true;
            }
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function driverIsHit(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return $this->instance->exists($item->getKey()) !== null;
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
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
        return (new driverStatistic())->setInfo(implode('<br />', (array) $this->instance->info()));
    }
}