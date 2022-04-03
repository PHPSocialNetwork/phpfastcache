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

class SapiDetector
{
    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function isWebScript(): bool
    {
        return \PHP_SAPI === 'apache2handler' || str_contains(\PHP_SAPI, 'handler') || isset($_SERVER['REQUEST_METHOD']);
    }

    public static function isCliScript(): bool
    {
        return (\PHP_SAPI === 'cli') || \defined('STDIN');
    }
}
