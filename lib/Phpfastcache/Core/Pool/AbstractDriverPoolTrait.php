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

namespace Phpfastcache\Core\Pool;

use Psr\Cache\CacheItemInterface;

/**
 * Trait AbstractCacheItemPoolTrait
 * @package Phpfastcache\Core\Pool
 */
trait AbstractDriverPoolTrait
{
    /**
     * @return bool
     */
    abstract protected function driverCheck(): bool;

    /**
     * @return bool
     */
    abstract protected function driverConnect(): bool;

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array [
     *      'd' => 'THE ITEM DATA'
     *      't' => 'THE ITEM DATE EXPIRATION'
     *      'g' => 'THE ITEM TAGS'
     * ]
     *
     */
    abstract protected function driverRead(CacheItemInterface $item);

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     */
    abstract protected function driverWrite(CacheItemInterface $item): bool;

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     */
    abstract protected function driverDelete(CacheItemInterface $item): bool;

    /**
     * @return bool
     */
    abstract protected function driverClear(): bool;
}