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

namespace Phpfastcache\Drivers\Failfiles;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Drivers\Files\Driver as FilesDriver;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package Phpfastcache\Drivers\Files2
 */
class Driver extends FilesDriver
{
    /**
     * @return bool
     * @throws PhpfastcacheDriverException
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        throw new PhpfastcacheDriverException('Error code found: ' . \bin2hex(\random_bytes(8)));
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        throw new PhpfastcacheDriverException('Error code found: ' . \bin2hex(\random_bytes(8)));
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        throw new PhpfastcacheDriverException('Error code found: ' . \bin2hex(\random_bytes(8)));
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverException
     */
    protected function driverClear(): bool
    {
        throw new PhpfastcacheDriverException('Error code found: ' . \bin2hex(\random_bytes(8)));
    }
}
