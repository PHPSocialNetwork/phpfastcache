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

namespace Phpfastcache\Drivers\Apcu;

use Phpfastcache\Core\Pool\{
    DriverBaseTrait, ExtendedCacheItemPoolInterface
};
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{
    PhpfastcacheInvalidArgumentException
};
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
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \extension_loaded('apcu') && ini_get('apc.enabled');
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        return true;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $ttl = $item->getExpirationDate()->getTimestamp() - \time();

            return (bool)apcu_store($item->getKey(), $this->driverPreWrap($item), ($ttl > 0 ? $ttl : 0));
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $data = apcu_fetch($item->getKey(), $success);
        if ($success === false) {
            return null;
        }

        return $data;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return (bool)apcu_delete($item->getKey());
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return @apcu_clear_cache();
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
        $stats = (array)apcu_cache_info();
        $date = (new \DateTime())->setTimestamp($stats['start_time']);

        return (new DriverStatistic())
            ->setData(\implode(', ', \array_keys($this->itemInstances)))
            ->setInfo(\sprintf("The APCU cache is up since %s, and have %d item(s) in cache.\n For more information see RawData.", $date->format(\DATE_RFC2822),
                $stats['num_entries']))
            ->setRawData($stats)
            ->setSize((int)$stats['mem_size']);
    }
}
