<?php
declare(strict_types=1);
namespace Phpfastcache\Autoload;

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

/**
 * Register Mock Autoload
 */
\spl_autoload_register(function ($entity) {
    $module = \explode('\\', $entity, 2);
    if ($module[ 0 ] !== 'Phpfastcache') {
        /**
         * Not a part of phpFastCache file
         * then we return here.
         */
        return;
    }

    $entity = \str_replace('\\', '/', $entity);
    $path = __DIR__ . DIRECTORY_SEPARATOR . $entity . '.php';

    if (\is_readable($path)) {
        require_once $path;
    }
});