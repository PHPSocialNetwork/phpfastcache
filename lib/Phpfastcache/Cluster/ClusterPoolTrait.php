<?php

/**
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 * @author  Georges.L (Geolim4)  <contact@geolim4.com>
 */
declare(strict_types=1);

namespace Phpfastcache\Cluster;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;

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
     * @return ?array
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        return null;
    }

    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        return true;
    }

    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        return true;
    }

    protected function driverClear(): bool
    {
        return true;
    }
}
