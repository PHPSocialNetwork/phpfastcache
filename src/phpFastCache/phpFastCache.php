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

use phpFastCache\CacheManager;
use phpFastCache\Util\OpenBaseDir;
define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));
require_once __DIR__."/Util/OpenBaseDir.php";
/**
 * Register Autoload
 */
spl_autoload_register(function ($entity) {
    // Explode is faster than substr & strstr also more control
    $module = explode('\\',$entity,2);
    if ($module[0] !== 'phpFastCache') {
        /**
         * Not a part of phpFastCache file
         * then we return here.
         */
        return;
    }
    if(!OpenBaseDir::checkBaseDir(__DIR__)) {
        /*
         * in case system have open base_dir, it will check ONE time only for the __DIR__
         * If open_base_dir is NULL, it skip checking
         */
        return;
    }

    $entity = str_replace('\\', '/', $module[1]);
    $path = __DIR__ . '/' . $entity . '.' . PHP_EXT;
    if (is_readable($path)) {
        require_once $path;
    }
});

/**
 * phpFastCache() Full alias
 * @param string $storage
 * @param array $config
 * @return mixed
 */
if (!function_exists("phpFastCache")) {
    function phpFastCache($storage = 'auto', $config = array())
    {
        return CacheManager::getInstance($storage, $config);
    }
}
/**
 * __c() Short alias
 * @param string $storage
 * @param array $config
 * @return mixed
 */
if (!function_exists("__c")) {
    function __c($storage = 'auto', $config = array())
    {
        return CacheManager::getInstance($storage, $config);
    }
}