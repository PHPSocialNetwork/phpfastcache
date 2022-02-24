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

namespace Phpfastcache\Drivers\Devrandom;

use DateInterval;
use DateTimeImmutable;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
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
        $stat->setInfo('[Devrandom] A void info string')
            ->setSize(0)
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData(false);

        return $stat;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return true;
    }

    /**
     * @return array
     */
    protected function driverRead(CacheItemInterface $item): ?array
    {
        $chanceOfRetrieval = $this->getConfig()->getChanceOfRetrieval();
        $ttl = $this->getConfig()->getDefaultTtl();

        if (random_int(0, 100) < $chanceOfRetrieval) {
            return [
                self::DRIVER_DATA_WRAPPER_INDEX => bin2hex(random_bytes($this->getConfig()->getDataLength())),
                self::DRIVER_TAGS_WRAPPER_INDEX => [],
                self::DRIVER_EDATE_WRAPPER_INDEX => (new DateTimeImmutable())->add(new DateInterval("PT{$ttl}S")),
            ];
        }

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
