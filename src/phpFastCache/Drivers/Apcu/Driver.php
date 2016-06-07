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

namespace phpFastCache\Drivers\Apcu;

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;

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
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        if (extension_loaded('apcu') && ini_get('apc.enabled')) {
            return true;
        } else {
            return false;
        }
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

            return apc_store($item->getKey(), $this->driverPreWrap($item), ($ttl > 0 ? $ttl : 0));
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
        $data = apc_fetch($item->getKey(), $success);
        if ($success === false) {
            return null;
        }

        return $data;
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
            return apc_delete($item->getKey());
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return @apc_clear_cache() && @apc_clear_cache('user');
    }

    /**
     * @return bool
     */
    protected function driverConnect()
    {
        return true;
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
        $stats = (array) apc_cache_info('user');
        $date = (new \DateTime())->setTimestamp($stats[ 'start_time' ]);

        return (new driverStatistic())
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setInfo(sprintf("The APCU cache is up since %s, and have %d item(s) in cache.\n For more information see RawData.", $date->format(DATE_RFC2822), $stats[ 'num_entries' ]))
          ->setRawData($stats)
          ->setSize($stats[ 'mem_size' ]);
    }
}