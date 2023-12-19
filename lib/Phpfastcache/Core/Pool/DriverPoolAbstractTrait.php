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
use Phpfastcache\Exceptions\PhpfastcacheUnsupportedMethodException;
use Phpfastcache\Wiki;
use Psr\Cache\CacheItemInterface;

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
     * @param ExtendedCacheItemInterface ...$items
     * @return array<array<string, mixed>>
     * @throws PhpfastcacheUnsupportedMethodException
     */
    protected function driverReadMultiple(ExtendedCacheItemInterface ...$items): array
    {
        throw new PhpfastcacheUnsupportedMethodException();
    }

    /**
     * @return \Traversable<int, string>
     * @throws PhpfastcacheUnsupportedMethodException
     */
    protected function driverReadAllKeys(string $pattern = ''): iterable
    {
        throw new PhpfastcacheUnsupportedMethodException(
            sprintf(
                'The "readAll" operation is unsupported by the the "%s" driver. See the Wiki for more information at %s',
                $this->getDriverName(),
                Wiki::FETCH_ALL_KEY_URL,
            )
        );
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    abstract protected function driverWrite(ExtendedCacheItemInterface $item): bool;

    /**
     * @param ExtendedCacheItemInterface ...$item
     * @return bool
     * @throws PhpfastcacheUnsupportedMethodException
     */
    protected function driverWriteMultiple(ExtendedCacheItemInterface ...$item): bool
    {
        /**
         * @todo Implement bulk writes to be for v10:
         * For methods commit() and saveMultiple()
         */
        throw new PhpfastcacheUnsupportedMethodException();
    }


    /**
     * @param string $key
     * @param string $encodedKey
     * @return bool
     */
    abstract protected function driverDelete(string $key, string $encodedKey): bool;

    /**
     * @param string[] $keys
     * @return bool
     * @throws PhpfastcacheUnsupportedMethodException
     */
    protected function driverDeleteMultiple(array $keys): bool
    {
        throw new PhpfastcacheUnsupportedMethodException();
    }

    /**
     * @return bool
     */
    abstract protected function driverClear(): bool;

    /**
     * @param CacheItemInterface $item
     * @param string $expectedClassType
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function assertCacheItemType(CacheItemInterface $item, string $expectedClassType): void
    {
        if (!($item instanceof $expectedClassType)) {
            throw new PhpfastcacheInvalidArgumentException(
                \sprintf('Cross-driver type confusion detected: Expected "%s" object, got "%s"', $expectedClassType, $item::class)
            );
        }
    }
}
