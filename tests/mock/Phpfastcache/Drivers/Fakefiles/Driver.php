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

namespace Phpfastcache\Drivers\Fakefiles;

use Phpfastcache\Drivers\Files\Driver as FilesDriver;

/**
 * Class Driver
 */
class Driver extends FilesDriver
{
    public function driverCheck(): bool
    {
        return false;
    }
}
