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

namespace phpFastCache\Drivers\Memcached;

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\MemcacheDriverCollisionDetectorTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;
use Memcached as MemcachedSoftware;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    use MemcacheDriverCollisionDetectorTrait;
    /**
     * @var array
     */
    protected $deferredList = [];

    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct(array $config = [])
    {
        self::checkCollision('Memcached');
        $this->setup($config);

        if (!$this->driverCheck()) {
            throw new phpFastCacheDriverCheckException(sprintf(self::DRIVER_CHECK_FAILURE, 'Memcached'));
        } else {
            $this->instance = new MemcachedSoftware();
            $this->driverConnect();
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        return class_exists('Memcached');
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

            return $this->instance->set($item->getKey(), $this->driverPreWrap($item), $ttl);
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
                if (!$this->instance->addServer($server[ 0 ], $server[ 1 ])) {
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
            return $this->instance->get($item->getKey()) !== null;
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /********************
     *
     * PSR-6 Methods
     *
     *******************/

    /**
     * @param string $key
     * @return \phpFastCache\Cache\ExtendedCacheItemInterface
     * @throws \InvalidArgumentException
     */
    public function getItem($key)
    {
        if (is_string($key)) {
            if (!array_key_exists($key, $this->itemInstances)) {
                new Item($this, $key);
            }
        } else {
            throw new \InvalidArgumentException(sprintf('$key must be a string, got type "%s" instead.',
              gettype($key)));
        }

        return $this->itemInstances[ $key ];
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setItem(CacheItemInterface $item)
    {
        if (__NAMESPACE__ . '\\Item' === get_class($item)) {
            $this->itemInstances[ $item->getKey() ] = $item;

            return $this;
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid Item Class "%s" for this driver.',
              get_class($item)));
        }
    }

    /**
     * @param array $keys
     * @return CacheItemInterface[]
     * @throws \InvalidArgumentException
     */
    public function getItems(array $keys = [])
    {
        $collection = [];
        foreach ($keys as $key) {
            $collection[ $key ] = $this->getItem($key);
        }

        return $collection;
    }

    /**
     * @param string $key
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return $this->driverClear();
    }

    /**
     * @param string $key
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function deleteItem($key)
    {
        if ($this->hasItem($key)) {
            return $this->driverDelete($this->getItem($key));
        }

        return false;
    }

    /**
     * @param array $keys
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function deleteItems(array $keys)
    {
        $return = null;
        foreach ($keys as $key) {
            $result = $this->deleteItem($key);
            if ($result !== false) {
                $return = $result;
            }
        }

        return (bool)$return;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function save(CacheItemInterface $item)
    {
        return $this->driverWrite($item);
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return \Psr\Cache\CacheItemInterface
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->deferredList[ $item->getKey() ] = $item;
    }

    /**
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    public function commit()
    {
        $return = null;
        foreach ($this->deferredList as $key => $item) {
            $result = $this->driverWrite($item);
            if ($return !== false) {
                unset($this->deferredList[ $key ]);
                $return = $result;
            }
        }

        return (bool) $return;
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
        return (new driverStatistic())->setInfo(implode('<br />', (array) $this->instance->getStats()));
    }
}