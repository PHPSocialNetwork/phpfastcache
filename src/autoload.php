<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));
require_once 'phpFastCache/Legacy/aliases.' . PHP_EXT;

/**
 * Register Autoload
 */
spl_autoload_register(function ($entity) {
    if (substr($entity, 0, 13) !== 'phpFastCache\\') {
        /**
         * Not a part of phpFastCache file
         * then we return here.
         */
        return;
    }

    $entity = str_replace('\\', '/', $entity);

    $path = __DIR__ . '/' . $entity . '.' . PHP_EXT;
    if (is_readable($path)) {
        require_once $path;
    }
});