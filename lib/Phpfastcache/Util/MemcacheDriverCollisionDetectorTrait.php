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

namespace Phpfastcache\Util;

trait MemcacheDriverCollisionDetectorTrait
{
    protected static string $driverUsed;

    public static function checkCollision(string $driverName): bool
    {
        $constantName = __NAMESPACE__ . '\MEMCACHE_DRIVER_USED';

        if ($driverName) {
            if (!defined($constantName)) {
                define($constantName, $driverName);

                return true;
            }

            if (constant($constantName) !== $driverName) {
                trigger_error(
                    'Memcache collision detected, you used both Memcache and Memcached driver in your script, this may leads to unexpected behaviours',
                    E_USER_WARNING
                );

                return false;
            }

            return true;
        }

        return false;
    }
}
