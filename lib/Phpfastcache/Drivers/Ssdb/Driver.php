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

namespace Phpfastcache\Drivers\Ssdb;

use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use phpssdb\Core\SimpleSSDB;
use phpssdb\Core\SSDBException;
use phpssdb\Core\SSDB;

/**
 * @property SimpleSSDB $instance Instance of driver service
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        static $driverCheck;

        return $driverCheck ?? ($driverCheck = class_exists(SSDB::class));
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

            return true;
        } catch (SSDBException $e) {
            throw new PhpfastcacheDriverCheckException('Ssdb failed to connect with error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $val = $this->instance->get($item->getEncodedKey());

        if (empty($val)) {
            return null;
        }

        return $this->decode($val);
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return (bool)$this->instance->setx($item->getEncodedKey(), $this->encode($this->driverPreWrap($item)), $item->getTtl());
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return (bool)$this->instance->del($item->getEncodedKey());
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        $this->instance->flushdb('kv');

        // Status not returned, then we assume its true
        return true;
    }
}
