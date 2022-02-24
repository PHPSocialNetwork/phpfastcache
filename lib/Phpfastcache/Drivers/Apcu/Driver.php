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

namespace Phpfastcache\Drivers\Apcu;

use DateTimeImmutable;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Util\SapiDetector;

/**
 * Class Driver
 *
 * @property Config $config
 */
class Driver implements AggregatablePoolInterface, ExtendedCacheItemPoolInterface
{
    use TaggableCacheItemPoolTrait;

    public function driverCheck(): bool
    {
        return \extension_loaded('apcu') && ((ini_get('apc.enabled') && SapiDetector::isWebScript()) || (ini_get('apc.enable_cli') && SapiDetector::isCliScript()));
    }

    public function getStats(): DriverStatistic
    {
        $stats = (array) apcu_cache_info();
        $date = (new DateTimeImmutable())->setTimestamp($stats['start_time']);

        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo(
                sprintf(
                    "The APCU cache is up since %s, and have %d item(s) in cache.\n For more information see RawData.",
                    $date->format(\DATE_RFC2822),
                    $stats['num_entries']
                )
            )
            ->setRawData($stats)
            ->setSize((int) $stats['mem_size']);
    }

    protected function driverConnect(): bool
    {
        return true;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return (bool) apcu_store($item->getKey(), $this->driverPreWrap($item), $item->getTtl());
    }

    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $data = apcu_fetch($item->getKey(), $success);

        if (false === $success) {
            return null;
        }

        return $data;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return (bool) apcu_delete($item->getKey());
    }

    protected function driverClear(): bool
    {
        return @apcu_clear_cache();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
