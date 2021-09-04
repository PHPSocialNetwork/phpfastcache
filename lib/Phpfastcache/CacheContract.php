<?php

/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Squiz Pty Ltd <products@squiz.net>
 * @author Squiz Pty Ltd <products@squiz.net>
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
    }//end __construct()


    /**
     * @param  string                    $cacheKey
     * @param  callable                  $callback
     * @param  DateInterval|integer|null $expiresAfter
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function get(string $cacheKey, callable $callback, DateInterval|int $expiresAfter = null): mixed
    {
        $cacheItem = $this->cacheInstance->getItem($cacheKey);

        if (! $cacheItem->isHit()) {
            /*
            *
    * Parameter $cacheItem will be available as of 8.0.6
            */
            $cacheItem->set($callback($cacheItem));
            if ($expiresAfter) {
                $cacheItem->expiresAfter($expiresAfter);
            }

            $this->cacheInstance->save($cacheItem);
        }

        return $cacheItem->get();
    }//end get()


    public function __invoke(...$args): mixed
    {
        return $this->get(...$args);
    }//end __invoke()
}
