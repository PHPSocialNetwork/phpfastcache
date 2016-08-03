<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Lucas Brucksch <support@hammermaps.de>
 *
 */

namespace phpFastCache\Drivers\Zendshm;

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver (zend memory cache)
 * Requires Zend Data Cache Functions from ZendServer
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
        if (extension_loaded('Zend Data Cache') && function_exists('zend_shm_cache_store')) {
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

            return zend_shm_cache_store($item->getKey(), $this->driverPreWrap($item), ($ttl > 0 ? $ttl : 0));
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
        $data = zend_shm_cache_fetch($item->getKey());
        if ($data === false) {
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
            return zend_shm_cache_delete($item->getKey());
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return @zend_shm_cache_clear();
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
        if(function_exists('zend_shm_cache_info')) {
            $stats = (array)zend_shm_cache_info();
            return (new driverStatistic())
                ->setData(implode(', ', array_keys($this->itemInstances)))
                ->setInfo(sprintf("The Zend memory have %d item(s) in cache.\n For more information see RawData.", $stats['items_total']))
                ->setRawData($stats)
                ->setSize($stats['memory_total']);
        } else {
            /** zend_shm_cache_info supported V8 or higher */
            return (new driverStatistic())
                ->setData(implode(', ', array_keys($this->itemInstances)))
                ->setInfo("The Zend memory statistics is only supported by ZendServer V8 or higher")
                ->setRawData(null)
                ->setSize(0);
        }
    }
}