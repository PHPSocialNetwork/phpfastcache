<?php

/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Wincache;

use DateTimeImmutable;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * @property Config $config Return the config object
 */
class Driver implements AggregatablePoolInterface, ExtendedCacheItemPoolInterface
{
    use TaggableCacheItemPoolTrait;

    public function driverCheck(): bool
    {
        return \extension_loaded('wincache') && \function_exists('wincache_ucache_set');
    }

    public function getStats(): DriverStatistic
    {
        $memInfo = wincache_ucache_meminfo();
        $info = wincache_ucache_info();
        $date = (new DateTimeImmutable())->setTimestamp(time() - $info['total_cache_uptime']);

        return (new DriverStatistic())
            ->setInfo(sprintf("The Wincache daemon is up since %s.\n For more information see RawData.", $date->format(\DATE_RFC2822)))
            ->setSize($memInfo['memory_free'] - $memInfo['memory_total'])
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData($memInfo);
    }

    protected function driverConnect(): bool
    {
        return true;
    }

    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $val = wincache_ucache_get($item->getKey(), $suc);

        if (false === $suc) {
            return null;
        }

        return $val;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     *
     * @return mixed
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return wincache_ucache_set($item->getKey(), $this->driverPreWrap($item), $item->getTtl());
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return wincache_ucache_delete($item->getKey());
    }

    protected function driverClear(): bool
    {
        return wincache_ucache_clear();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
