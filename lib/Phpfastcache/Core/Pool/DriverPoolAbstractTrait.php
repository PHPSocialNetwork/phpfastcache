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

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

trait DriverPoolAbstractTrait
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
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    abstract protected function driverRead(ExtendedCacheItemInterface $item): ?array;

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    abstract protected function driverWrite(ExtendedCacheItemInterface $item): bool;

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    abstract protected function driverDelete(ExtendedCacheItemInterface $item): bool;

    /**
     * @return bool
     */
    abstract protected function driverClear(): bool;

    /**
     * @param ExtendedCacheItemInterface $item
     * @param string $expectedClassType
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function assertCacheItemType(ExtendedCacheItemInterface $item, string $expectedClassType): void
    {
        if (!($item instanceof $expectedClassType)) {
            throw new PhpfastcacheInvalidArgumentException(
                \sprintf('Cross-driver type confusion detected: Expected "%s" object, got "%s"', $expectedClassType, $item::class)
            );
        }
    }
}
