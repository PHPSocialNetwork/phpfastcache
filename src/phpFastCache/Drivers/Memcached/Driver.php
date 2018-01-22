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

namespace phpFastCache\Drivers\Memcached;

use Memcached as MemcachedSoftware;
use phpFastCache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\{
  phpFastCacheInvalidArgumentException, phpFastCacheDriverException
};
use phpFastCache\Util\{ArrayObject, MemcacheDriverCollisionDetectorTrait};
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property MemcachedSoftware $instance
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait {
        __construct as protected __parentConstruct;
    }
    use MemcacheDriverCollisionDetectorTrait;

    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct(array $config = [], $instanceId)
    {
        self::checkCollision('Memcached');
        $this->__parentConstruct($config, $instanceId);
    }

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return class_exists('Memcached');
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        $this->instance = new MemcachedSoftware();
        $this->instance->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        $servers = (!empty($this->config[ 'servers' ]) && is_array($this->config[ 'servers' ]) ? $this->config[ 'servers' ] : []);
        if (count($servers) < 1) {
            $servers = [
              [
                'host' => !empty($this->config[ 'host' ]) ? $this->config[ 'host' ] : '127.0.0.1',
                'path' => !empty($this->config[ 'path' ]) ? $this->config[ 'path' ] : false,
                'port' => !empty($this->config[ 'port' ]) ? $this->config[ 'port' ] : 11211,
                'sasl_user' => !empty($this->config[ 'sasl_user' ]) ? $this->config[ 'sasl_user' ] : false,
                'sasl_password' =>!empty($this->config[ 'sasl_password' ]) ? $this->config[ 'sasl_password' ]: false,
              ],
            ];
        }

        foreach ($servers as $server) {
            try {
                /**
                 * If path is provided we consider it as an UNIX Socket
                 */
                if(!empty($server[ 'path' ]) && !$this->instance->addServer($server[ 'path' ], 0)){
                    $this->fallback = true;
                }else if (!empty($server[ 'host' ]) && !$this->instance->addServer($server[ 'host' ], $server[ 'port' ])) {
                    $this->fallback = true;
                }

                if (!empty($server[ 'sasl_user' ]) && !empty($server[ 'sasl_password' ])) {
                    $this->instance->setSaslAuthData($server[ 'sasl_user' ], $server[ 'sasl_password' ]);
                }

            } catch (\Exception $e) {
                $this->fallback = true;
            }
        }

        /**
         * Since Memcached does not throw
         * any error if not connected ...
         */
        $version = $this->instance->getVersion();
        if(!$version || $this->instance->getResultCode() !== MemcachedSoftware::RES_SUCCESS){
            throw new phpFastCacheDriverException('Memcached seems to not be connected');
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

        if ($val === false) {
            return null;
        } else {
            return $val;
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
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
            return $this->instance->delete($item->getKey());
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->instance->flush();
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
        $stats = current($this->instance->getStats());
        $stats[ 'uptime' ] = (isset($stats[ 'uptime' ]) ? $stats[ 'uptime' ] : 0);
        $stats[ 'version' ] = (isset($stats[ 'version' ]) ? $stats[ 'version' ] : $this->instance->getVersion());
        $stats[ 'bytes' ] = (isset($stats[ 'bytes' ]) ? $stats[ 'version' ] : 0);

        $date = (new \DateTime())->setTimestamp(time() - $stats[ 'uptime' ]);

        return (new DriverStatistic())
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setInfo(sprintf("The memcache daemon v%s is up since %s.\n For more information see RawData.", $stats[ 'version' ], $date->format(DATE_RFC2822)))
          ->setRawData($stats)
          ->setSize((int)$stats[ 'bytes' ]);
    }

    /**
     * @return ArrayObject
     */
    public function getDefaultConfig(): ArrayObject
    {
        $defaultConfig = new ArrayObject();

        $defaultConfig[ 'servers' ] = [
          [
            'host' => '127.0.0.1',
            'port' => 11211,
            'sasl_user' => false,
            'sasl_password' => false,
          ],
        ];

        return $defaultConfig;
    }
}