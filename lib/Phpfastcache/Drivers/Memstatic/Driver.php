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

namespace Phpfastcache\Drivers\Memstatic;

use Phpfastcache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{PhpfastcacheInvalidArgumentException};
use Psr\Cache\CacheItemInterface;


/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

    /**
     * @var array
     */
    protected $staticStack = [];

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return true;
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $stat = new DriverStatistic();
        $stat->setInfo('[Memstatic] A memory static driver')
            ->setSize(mb_strlen(serialize($this->staticStack)))
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData($this->staticStack);

        return $stat;
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        return true;
    }

    /**
     * @param CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        return $this->staticStack[$item->getKey()] ?? null;
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $this->staticStack[$item->getKey()] = $this->driverPreWrap($item);
            return true;
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
            $key = $item->getKey();
            if (isset($this->staticStack[$key])) {
                unset($this->staticStack[$key]);
                return true;
            }
            return false;
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
        unset($this->staticStack);
        $this->staticStack = [];
        return true;
    }
}
