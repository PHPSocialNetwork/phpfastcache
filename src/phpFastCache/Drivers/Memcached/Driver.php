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

use Memcached as MemcachedSoftware;
use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\MemcacheDriverCollisionDetectorTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property MemcachedSoftware $instance
 */
class Driver extends DriverAbstract
{
    use MemcacheDriverCollisionDetectorTrait;

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
        return class_exists('Memcached');
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
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $val = $this->instance->get($item->getKey());

        if ($val === false) {
            return null;
        } else {
            return $val;
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
            return $this->instance->delete($item->getKey());
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return $this->instance->flush();
    }

    /**
     * @return bool
     */
    protected function driverConnect()
    {
        $this->instance = new MemcachedSoftware();
        $this->instance->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);

        $servers = (!empty($this->config[ 'servers' ]) && is_array($this->config[ 'servers' ]) ? $this->config[ 'servers' ] : []);
        if (count($servers) < 1) {
            $servers = [
              [
                'host' => !empty($this->config[ 'host' ]) ? $this->config[ 'host' ] : '127.0.0.1',
                'port' => !empty($this->config[ 'port' ]) ? $this->config[ 'port' ] : 11211,
                'sasl_user' => !empty($this->config[ 'sasl_user' ]) ? $this->config[ 'sasl_user' ] : false,
                'sasl_password' => !empty($this->config[ 'sasl_password' ]) ? $this->config[ 'sasl_password' ] : false,
              ],
            ];
        }

        foreach ($servers as $server) {
            try {
                if (!$this->instance->addServer($server['host'], $server['port'])) {
                    $this->fallback = true;
                }
                if(!empty($server[ 'sasl_user' ]) && !empty($server[ 'sasl_password'])){
                    $this->instance->setSaslAuthData($server[ 'sasl_user' ], $server[ 'sasl_password']);
                }
            } catch (\Exception $e) {
                $this->fallback = true;
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
        $stats = (array) $this->instance->getStats();
        $stats[ 'uptime' ] = (isset($stats[ 'uptime' ]) ? $stats[ 'uptime' ] : 0);
        $stats[ 'version' ] = (isset($stats[ 'version' ]) ? $stats[ 'version' ] : 'UnknownVersion');
        $stats[ 'bytes' ] = (isset($stats[ 'bytes' ]) ? $stats[ 'version' ] : 0);

        $date = (new \DateTime())->setTimestamp(time() - $stats[ 'uptime' ]);

        return (new driverStatistic())
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setInfo(sprintf("The memcache daemon v%s is up since %s.\n For more information see RawData.", $stats[ 'version' ], $date->format(DATE_RFC2822)))
          ->setRawData($stats)
          ->setSize($stats[ 'bytes' ]);
    }
}