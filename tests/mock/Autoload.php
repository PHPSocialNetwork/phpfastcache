<?php

declare(strict_types=1);

namespace Phpfastcache\Autoload;

/*
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

/*
 * Register Mock Autoload
 */
spl_autoload_register(function ($entity): void {
    $module = explode('\\', $entity, 2);
    if ('Phpfastcache' !== $module[0]) {
        /*
         * Not a part of phpFastCache file
         * then we return here.
         */
        return;
    }

    $entity = str_replace('\\', '/', $entity);
    $path = __DIR__ . \DIRECTORY_SEPARATOR . $entity . '.php';

    if (is_readable($path)) {
        require_once $path;
    }
});
