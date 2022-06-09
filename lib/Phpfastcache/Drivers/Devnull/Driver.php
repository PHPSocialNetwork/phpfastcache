<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

declare(strict_types=1);

namespace Phpfastcache\Drivers\Devnull;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @method Config getConfig()
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use TaggableCacheItemPoolTrait;

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
        $stat->setInfo('[Devnull] A void info string')
            ->setSize(0)
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData(null);

        return $stat;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    protected function driverRead(CacheItemInterface $item): ?array
    {
        return null;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return true;
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        return true;
    }
}
