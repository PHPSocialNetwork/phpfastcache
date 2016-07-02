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

define('PFC_PHP_EXT', 'php');
define('PFC_BIN_DIR', __DIR__ . '/../bin/');

/**
 * Register Autoload
 */
spl_autoload_register(function ($entity) {
    $module = explode('\\', $entity, 2);
    if (!in_array($module[ 0 ], ['phpFastCache', 'Psr'])) {
        /**
         * Not a part of phpFastCache file
         * then we return here.
         */
        return;
    } else if (strpos($entity, 'Psr\Cache') === 0) {
        $path = PFC_BIN_DIR . 'legacy/Psr/Cache/src/' . substr(strrchr($entity, '\\'), 1) . '.' . PFC_PHP_EXT;

        if (is_readable($path)) {
            require_once $path;
        }else{
            trigger_error('Cannot locate the Psr/Cache files', E_USER_ERROR);
        }
        return;
    }

    $entity = str_replace('\\', '/', $entity);
    $path = __DIR__ . '/' . $entity . '.' . PFC_PHP_EXT;

    if (is_readable($path)) {
        require_once $path;
    }
});

if (class_exists('Composer\Autoload\ClassLoader')) {
    trigger_error('Your project already makes use of Composer. You SHOULD use the composer dependency "phpfastcache/phpfastcache" instead of hard-autoloading.',
      E_USER_WARNING);
}