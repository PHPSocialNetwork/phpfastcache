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
    /**
     * @var string
     */
    protected static $driverUsed;

    /**
     * @param $driverName
     * @return bool
     */
    public static function checkCollision($driverName): bool
    {
        $CONSTANT_NAME = __NAMESPACE__ . '\MEMCACHE_DRIVER_USED';

        if ($driverName && is_string($driverName)) {
            if (!defined($CONSTANT_NAME)) {
                define($CONSTANT_NAME, $driverName);

                return true;
            } else {
                if (constant($CONSTANT_NAME) !== $driverName) {
                    trigger_error(
                        'Memcache collision detected, you used both Memcache and Memcached driver in your script, this may leads to unexpected behaviours',
                        E_USER_WARNING
                    );

                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
