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

namespace phpFastCache\Drivers\Zenddisk;

use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver (zend disk cache)
 * Requires Zend Data Cache Functions from ZendServer
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
        if (extension_loaded('Zend Data Cache') && function_exists('zend_disk_cache_store')) {
            return true;
        } else {
            return false;
        }
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
            $ttl = $item->getExpirationDate()->getTimestamp() - time();

            return zend_disk_cache_store($item->getKey(), $this->driverPreWrap($item), ($ttl > 0 ? $ttl : 0));
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
        $data = zend_disk_cache_fetch($item->getKey());
        if ($data === false) {
            return null;
        }

        return $data;
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
            return zend_disk_cache_delete($item->getKey());
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return @zend_disk_cache_clear();
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
     * @return string
     */
    public function getHelp()
    {
        return <<<HELP
<p>
This driver rely on Zend Server 8.5+, see: http://www.zend.com/en/products/zend_server
</p>
HELP;
    }

    /**
     * @return DriverStatistic
     */
    public function getStats()
    {
        $stat = new DriverStatistic();
        $stat->setInfo('[ZendDisk] A void info string')
          ->setSize(0)
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setRawData(false);

        return $stat;
    }
}