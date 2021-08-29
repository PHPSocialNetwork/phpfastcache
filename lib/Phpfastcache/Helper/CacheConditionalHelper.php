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

namespace Phpfastcache\Helper;

use DateInterval;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class CacheConditional
 * @package phpFastCache\Helper
 */
class CacheConditionalHelper
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cacheInstance;

    /**
     * CachePromise constructor.
     * @param CacheItemPoolInterface $cacheInstance
     */
    public function __construct(CacheItemPoolInterface $cacheInstance)
    {
        $this->cacheInstance = $cacheInstance;
    }

    /**
     * @param string $cacheKey
     * @param callable $callback
     * @param int|DateInterval $expiresAfter
     * @return mixed
     */
    public function get(string $cacheKey, callable $callback, $expiresAfter = null)
    {
        $cacheItem = $this->cacheInstance->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            /** Parameter $cacheItem will be available as of 8.0.6 */
            $cacheItem->set($callback($cacheItem));
            if ($expiresAfter) {
                $cacheItem->expiresAfter($expiresAfter);
            }
            $this->cacheInstance->save($cacheItem);
        }

        return $cacheItem->get();
    }
}
