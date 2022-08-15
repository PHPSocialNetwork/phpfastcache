<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Georges.L (Geolim4) <contact@geolim4.com>
 *
 */

declare(strict_types=1);

namespace Phpfastcache\Cluster;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Psr\Cache\CacheItemInterface;

trait ClusterPoolTrait
{
    protected function driverCheck(): bool
    {
        return true;
    }

    protected function driverConnect(): bool
    {
        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        return null;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return true;
    }
}
