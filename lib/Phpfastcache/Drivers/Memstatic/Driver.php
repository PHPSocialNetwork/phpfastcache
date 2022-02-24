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

namespace Phpfastcache\Drivers\Memstatic;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * Class Driver
 *
 * @property Config $config
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use TaggableCacheItemPoolTrait;

    protected array $staticStack = [];

    public function driverCheck(): bool
    {
        return true;
    }

    protected function driverConnect(): bool
    {
        return true;
    }

    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        return $this->staticStack[$item->getKey()] ?? null;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        $this->staticStack[$item->getKey()] = $this->driverPreWrap($item);

        return true;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        $key = $item->getKey();
        if (isset($this->staticStack[$key])) {
            unset($this->staticStack[$key]);

            return true;
        }

        return false;
    }

    protected function driverClear(): bool
    {
        $this->staticStack = null;
        $this->staticStack = [];

        return true;
    }

    public function getStats(): DriverStatistic
    {
        $stat = new DriverStatistic();
        $stat->setInfo('[Memstatic] A memory static driver')
            ->setSize(mb_strlen(serialize($this->staticStack)))
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData($this->staticStack);

        return $stat;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
