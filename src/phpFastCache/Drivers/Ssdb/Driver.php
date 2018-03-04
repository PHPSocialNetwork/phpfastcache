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

namespace phpFastCache\Drivers\Ssdb;

use phpFastCache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\{
  phpFastCacheInvalidArgumentException, phpFastCacheDriverCheckException, phpFastCacheDriverException
};
use phpFastCache\Util\ArrayObject;
use phpssdb\Core\{SimpleSSDB, SSDBException};
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property SimpleSSDB $instance Instance of driver service
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        static $driverCheck;
        if ($driverCheck === null) {
            return ($driverCheck = \class_exists('phpssdb\Core\SSDB'));
        }

        return $driverCheck;
    }

    /**
     * @return bool
     * @throws phpFastCacheDriverException
     */
    protected function driverConnect(): bool
    {
        try {
            $clientConfig = $this->getConfig();

            $this->instance = new SimpleSSDB($clientConfig[ 'host' ], $clientConfig[ 'port' ], $clientConfig[ 'timeout' ]);
            if (!empty($clientConfig[ 'password' ])) {
                $this->instance->auth($clientConfig[ 'password' ]);
            }

            if (!$this->instance) {
                return false;
            } else {
                return true;
            }
        } catch (SSDBException $e) {
            throw new phpFastCacheDriverCheckException('Ssdb failed to connect with error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $val = $this->instance->get($item->getEncodedKey());
        if ($val == false) {
            return null;
        } else {
            return $this->decode($val);
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return $this->instance->setx($item->getEncodedKey(), $this->encode($this->driverPreWrap($item)), $item->getTtl());
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
            return $this->instance->del($item->getEncodedKey());
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->instance->flushdb('kv');
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
        $stat = new DriverStatistic();
        $info = $this->instance->info();

        /**
         * Data returned by Ssdb are very poorly formatted
         * using hardcoded offset of pair key-value :-(
         */
        $stat->setInfo(\sprintf("Ssdb-server v%s with a total of %s call(s).\n For more information see RawData.", $info[ 2 ], $info[ 6 ]))
          ->setRawData($info)
          ->setData(\implode(', ', \array_keys($this->itemInstances)))
          ->setSize($this->instance->dbsize());

        return $stat;
    }
}