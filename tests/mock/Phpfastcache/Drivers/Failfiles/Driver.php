<?php

declare(strict_types=1);
/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

namespace Phpfastcache\Drivers\Failfiles;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Drivers\Files\Driver as FilesDriver;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 */
class Driver extends FilesDriver
{
    /**
     * @throws PhpfastcacheDriverException
     *
     * @return bool
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        throw new PhpfastcacheDriverException('Error code found: ' . bin2hex(random_bytes(8)));
    }

    /**
     * @throws PhpfastcacheDriverException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        throw new PhpfastcacheDriverException('Error code found: ' . bin2hex(random_bytes(8)));
    }

    /**
     * @throws PhpfastcacheDriverException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        throw new PhpfastcacheDriverException('Error code found: ' . bin2hex(random_bytes(8)));
    }

    /**
     * @throws PhpfastcacheDriverException
     */
    protected function driverClear(): bool
    {
        throw new PhpfastcacheDriverException('Error code found: ' . bin2hex(random_bytes(8)));
    }
}
