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

namespace Phpfastcache\Drivers\Redis;

use DateTime;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Redis as RedisClient;
use RedisCluster as RedisClusterClient;

/**
 * @property RedisClient|RedisClusterClient $instance
 * @method Config getConfig()
 */
abstract class DriverAbstract implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $val = $this->instance->get($item->getKey());
        if (!$val) {
            return null;
        }

        return $this->decode($val);
    }


    protected function driverReadMultiple(ExtendedCacheItemInterface ...$items): array
    {
        $keys = $this->getKeys($items);

        return array_combine($keys, array_map(
            fn($val) => $val ? $this->decode($val) : null,
            $this->instance->mGet($keys)
        ));
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, self::getItemClass());

        $ttl = $item->getExpirationDate()->getTimestamp() - time();

        /**
         * @see https://redis.io/commands/setex
         * @see https://redis.io/commands/expire
         */
        if ($ttl <= 0) {
            return $this->instance->expire($item->getKey(), 0);
        }

        return $this->instance->setex($item->getKey(), $ttl, $this->encode($this->driverPreWrap($item)));
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {

        return (bool) $this->instance->del($item->getKey());
    }
}
