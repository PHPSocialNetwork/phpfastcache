<?php

declare(strict_types=1);

namespace Phpfastcache\Autoload;

use Composer\Autoload\ClassLoader;
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

const PFC_PHP_EXT = 'php';
const PFC_BIN_DIR = __DIR__ . '/../../../bin/';
const PFC_LIB_DIR = __DIR__ . '/../../../lib/';
const PFC_TESTS_DIR = __DIR__ . '/../../../tests/lib/';
const PFC_TESTS_NS = 'Phpfastcache\\Tests\\';


\trigger_error('The legacy autoload will be removed in the next major release. Please include Phpfastcache through composer by running `composer require phpfastcache/phpfastcache`.', \E_USER_DEPRECATED);
/**
 * Register Autoload
 */
spl_autoload_register(
    static function ($entity): void {
        $module = explode('\\', $entity, 2);
        if (!\in_array($module[0], ['Phpfastcache', 'Psr'])) {
            /**
             * Not a part of phpFastCache file
             * then we return here.
             */
            return;
        }

        if (\strpos($entity, 'Psr\Cache') === 0) {
            $path = PFC_BIN_DIR . 'dependencies/Psr/Cache/src/' . substr(strrchr($entity, '\\'), 1) . '.' . PFC_PHP_EXT;

            if (\is_readable($path)) {
                require_once $path;
            } else {
                \trigger_error('Cannot locate the Psr/Cache files', E_USER_ERROR);
            }
            return;
        }

        if (\strpos($entity, 'Psr\SimpleCache') === 0) {
            $path = PFC_BIN_DIR . 'dependencies/Psr/SimpleCache/src/' . \substr(\strrchr($entity, '\\'), 1) . '.' . PFC_PHP_EXT;

            if (\is_readable($path)) {
                require_once $path;
            } else {
                \trigger_error('Cannot locate the Psr/SimpleCache files', E_USER_ERROR);
            }
            return;
        }

        $entityPath = str_replace('\\', '/', $entity);

        if(\strpos($entity, PFC_TESTS_NS) === 0){
            $path = PFC_TESTS_DIR . \str_replace(str_replace('\\', '/', PFC_TESTS_NS), '', $entityPath) . '.' . PFC_PHP_EXT;
        }else{
            $path = PFC_LIB_DIR . $entityPath . '.' . PFC_PHP_EXT;
        }

        $path = \realpath($path);
        if (\is_readable($path)) {
            require_once $path;
        }
    }
);

if ((!\defined('PFC_IGNORE_COMPOSER_WARNING') || !PFC_IGNORE_COMPOSER_WARNING) && \class_exists(ClassLoader::class)) {
    trigger_error(
        'Your project already makes use of Composer. You SHOULD use the composer dependency "phpfastcache/phpfastcache" instead of hard-autoloading.',
        E_USER_WARNING
    );
}
