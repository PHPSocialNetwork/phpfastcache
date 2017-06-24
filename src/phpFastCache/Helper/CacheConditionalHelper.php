<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace phpFastCache\Helper;

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
     */
    public function get($cacheKey, callable $callback)
    {
        $cacheItem = $this->cacheInstance->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            $cacheItem->set($callback());
            $this->cacheInstance->save($cacheItem);
        }

        return $cacheItem->get();
    }
}