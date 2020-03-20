<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Ssdb;

use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{PhpfastcacheDriverCheckException, PhpfastcacheDriverException, PhpfastcacheInvalidArgumentException};
use phpssdb\Core\{SimpleSSDB, SSDBException};
use Psr\Cache\CacheItemInterface;


/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property SimpleSSDB $instance Instance of driver service
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
{
    use DriverBaseTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        static $driverCheck;
        if ($driverCheck === null) {
            return ($driverCheck = class_exists('phpssdb\Core\SSDB'));
        }

        return $driverCheck;
    }

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
        $stat->setInfo(sprintf("Ssdb-server v%s with a total of %s call(s).\n For more information see RawData.", $info[2], $info[6]))
            ->setRawData($info)
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setSize($this->instance->dbsize());

        return $stat;
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverException
     */
    protected function driverConnect(): bool
    {
        try {
            $clientConfig = $this->getConfig();

            $this->instance = new SimpleSSDB($clientConfig->getHost(), $clientConfig->getPort(), $clientConfig->getTimeout());
            if (!empty($clientConfig->getPassword())) {
                $this->instance->auth($clientConfig->getPassword());
            }

            if (!$this->instance) {
                return false;
            }

            return true;
        } catch (SSDBException $e) {
            throw new PhpfastcacheDriverCheckException('Ssdb failed to connect with error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $val = $this->instance->get($item->getEncodedKey());
        if ($val == false) {
            return null;
        }

        return $this->decode($val);
    }

    /**
     * @param CacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return (bool)$this->instance->setx($item->getEncodedKey(), $this->encode($this->driverPreWrap($item)), $item->getTtl());
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return (bool)$this->instance->del($item->getEncodedKey());
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return (bool)$this->instance->flushdb('kv');
    }
}
