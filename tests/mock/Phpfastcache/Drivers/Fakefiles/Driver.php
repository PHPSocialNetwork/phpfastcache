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

namespace Phpfastcache\Drivers\Fakefiles;
use Phpfastcache\Drivers\Files\Driver as FilesDriver;

/**
 * Class Driver
 * @package Phpfastcache\Drivers\Files2
 */
class Driver extends FilesDriver
{
    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return false;
    }
}