<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Helper;

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
     * @param int|\DateInterval $expiresAfter
     * @return mixed
     */
    public function get(string $cacheKey, callable $callback, $expiresAfter = null)
    {
        $cacheItem = $this->cacheInstance->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            $cacheItem->set($callback());
            if ($expiresAfter) {
                $cacheItem->expiresAfter($expiresAfter);
            }
            $this->cacheInstance->save($cacheItem);
        }

        return $cacheItem->get();
    }
}