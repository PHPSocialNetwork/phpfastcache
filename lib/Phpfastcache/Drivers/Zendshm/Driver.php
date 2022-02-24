<?php

/**
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 * @author Lucas Brucksch <support@hammermaps.de>
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Zendshm;

use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

/**
 * Requires Zend Data Cache Functions from ZendServer
 *
 * @property Config $config Return the config object
 */
class Driver implements AggregatablePoolInterface, ExtendedCacheItemPoolInterface
{
    use TaggableCacheItemPoolTrait;

    public function driverCheck(): bool
    {
        return \extension_loaded('Zend Data Cache') && \function_exists('zend_shm_cache_store');
    }

    public function getHelp(): string
    {
        return <<<'HELP'
            <p>
            This driver rely on Zend Server 8.5+, see: https://www.zend.com/en/products/zend_server
            </p>
            HELP;
    }

    public function getStats(): DriverStatistic
    {
        $stats = (array) zend_shm_cache_info();

        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo(sprintf("The Zend memory have %d item(s) in cache.\n For more information see RawData.", $stats['items_total']))
            ->setRawData($stats)
            ->setSize($stats['memory_total']);
    }

    protected function driverConnect(): bool
    {
        return true;
    }

    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $data = zend_shm_cache_fetch($item->getKey());
        if (false === $data) {
            return null;
        }

        return $data;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     *
     * @return mixed
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        $ttl = $item->getExpirationDate()->getTimestamp() - time();

        return zend_shm_cache_store($item->getKey(), $this->driverPreWrap($item), ($ttl > 0 ? $ttl : 0));
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return (bool) zend_shm_cache_delete($item->getKey());
    }

    protected function driverClear(): bool
    {
        return @zend_shm_cache_clear();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
