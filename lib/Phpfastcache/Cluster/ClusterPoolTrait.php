<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author  Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Cluster;

use Psr\Cache\CacheItemInterface;

trait ClusterPoolTrait
{
    /**
     * @return bool
     */
    protected function driverCheck(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        return true;
    }

    /**
     * @param CacheItemInterface $item
     * @return null
     */
    protected function driverRead(CacheItemInterface $item)
    {
        return null;
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    protected function driverDelete(CacheItemInterface $item): bool
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
