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

namespace Phpfastcache;

use DateInterval;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class CacheContract
{
    protected CacheItemPoolInterface $cacheInstance;

    public function __construct(CacheItemPoolInterface $cacheInstance)
    {
        $this->cacheInstance = $cacheInstance;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $cacheKey, callable $callback, DateInterval|int $expiresAfter = null): mixed
    {
        $cacheItem = $this->cacheInstance->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            /*
            * Parameter $cacheItem will be available as of 8.0.6
            */
            $cacheItem->set($callback($cacheItem));
            if ($expiresAfter) {
                $cacheItem->expiresAfter($expiresAfter);
            }

            $this->cacheInstance->save($cacheItem);
        }

        return $cacheItem->get();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(string $cacheKey, callable $callback, DateInterval|int $expiresAfter = null): mixed
    {
        return $this->get($cacheKey, $callback, $expiresAfter);
    }
}
