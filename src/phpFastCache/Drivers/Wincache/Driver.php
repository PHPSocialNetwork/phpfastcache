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

namespace phpFastCache\Drivers\Wincache;

use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

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
        return extension_loaded('wincache') && function_exists('wincache_ucache_set');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return wincache_ucache_set($item->getKey(), $this->driverPreWrap($item), $item->getTtl());
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $val = wincache_ucache_get($item->getKey(), $suc);

        if ($suc === false) {
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
    protected function driverDelete(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return wincache_ucache_delete($item->getKey());
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return wincache_ucache_clear();
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
     * @return DriverStatistic
     */
    public function getStats()
    {
        $memInfo = wincache_ucache_meminfo();
        $info = wincache_ucache_info();
        $date = (new \DateTime())->setTimestamp(time() - $info[ 'total_cache_uptime' ]);

        return (new DriverStatistic())
          ->setInfo(sprintf("The Wincache daemon is up since %s.\n For more information see RawData.", $date->format(DATE_RFC2822)))
          ->setSize($memInfo[ 'memory_free' ] - $memInfo[ 'memory_total' ])
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setRawData($memInfo);
    }
}