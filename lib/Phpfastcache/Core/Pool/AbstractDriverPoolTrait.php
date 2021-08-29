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
     * @param CacheItemInterface $item
     * @return ?array
     */
    abstract protected function driverRead(CacheItemInterface $item): ?array;

    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    abstract protected function driverWrite(CacheItemInterface $item): bool;

    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    abstract protected function driverDelete(CacheItemInterface $item): bool;

    /**
     * @return bool
     */
    abstract protected function driverClear(): bool;
}
