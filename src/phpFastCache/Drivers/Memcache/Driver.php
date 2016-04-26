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

namespace phpFastCache\Drivers\Memcache;

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\MemcacheDriverCollisionDetectorTrait;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;
use Memcache as MemcacheSoftware;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    use MemcacheDriverCollisionDetectorTrait, StandardPsr6StructureTrait;

    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct(array $config = [])
    {
        self::checkCollision('Memcache');
        $this->setup($config);

        if (!$this->driverCheck()) {
            throw new phpFastCacheDriverException(sprintf(self::DRIVER_CHECK_FAILURE, 'Memcache'));
        } else {
            $this->instance = new MemcacheSoftware();
            $this->driverConnect();
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        return class_exists(MemcacheSoftware::class);
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
            // Memcache will only allow a expiration timer less than 2592000 seconds,
            // otherwise, it will assume you're giving it a UNIX timestamp.
            if ($ttl > 2592000) {
                $ttl = time() + $ttl;
            }

            return $this->instance->set($item->getKey(), $item->get(), false, $ttl);
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
        // return null if no caching
        // return value if in caching
        $x = $this->instance->get($key);

        if ($x === false) {
            return null;
        } else {
            return $x;
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
            return $this->instance->delete($item->getKey());
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    public function driverClear()
    {
        return $this->instance->flush();
    }

    /**
     * @return bool
     */
    public function driverConnect()
    {
        $servers = (!empty($this->config[ 'memcache' ]) && is_array($this->config[ 'memcache' ]) ? $this->config[ 'memcache' ] : []);
        if (count($servers) < 1) {
            $servers = [
              ['127.0.0.1', 11211],
            ];
        }

        foreach ($servers as $server) {
            try {
                if (!$this->instance->addserver($server[ 0 ], $server[ 1 ])) {
                    $this->fallback = true;
                }
            } catch (\Exception $e) {
                $this->fallback = true;
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
            return $this->get($item->getKey()) !== null;
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
     * @return array
     */
    public function getStats()
    {
        $res = [
          'info' => '',
          'size' => '',
          'data' => $this->instance->getstats(),
        ];

        return $res;
    }
}