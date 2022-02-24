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

namespace Phpfastcache\Drivers\Devnull;

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 *
 * @property Config $config Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use TaggableCacheItemPoolTrait;

    public function driverCheck(): bool
    {
        return true;
    }

    public function getStats(): DriverStatistic
    {
        $stat = new DriverStatistic();
        $stat->setInfo('[Devnull] A void info string')
            ->setSize(0)
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData(null);

        return $stat;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     *
     * @return mixed
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return true;
    }

    protected function driverRead(CacheItemInterface $item): ?array
    {
        return null;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return true;
    }

    protected function driverClear(): bool
    {
        return true;
    }

    protected function driverConnect(): bool
    {
        return true;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
