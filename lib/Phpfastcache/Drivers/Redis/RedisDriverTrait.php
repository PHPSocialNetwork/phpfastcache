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

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Redis as RedisClient;
use RedisCluster as RedisClusterClient;

/**
 * @property RedisClient|RedisClusterClient $instance
 * @method Config getConfig()
 */
trait RedisDriverTrait
{
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

    /**
     * @param ExtendedCacheItemInterface ...$items
     * @return array<array<string, mixed>>
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
     * @throws \RedisException
     */
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
     * @param string $key
     * @param string $encodedKey
     * @return bool
     * @throws \RedisException
     */
    protected function driverDelete(string $key, string $encodedKey): bool
    {
        return (bool) $this->instance->del($key);
    }

    /**
     * @param string[] $keys
     * @return bool
     */
    protected function driverDeleteMultiple(array $keys): bool
    {
        return (bool) $this->instance->del(...$keys);
    }
}
