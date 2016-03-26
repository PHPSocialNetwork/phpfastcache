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
define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));

if(!defined("PHPFASTCACHE_LEGACY")) {
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

        $entity = str_replace('\\', '/', $module[1]);
        $path = __DIR__ . '/' . $entity . '.' . PHP_EXT;
        if (is_readable($path)) {
            require_once $path;
        }
    });

} else {
    require_once __DIR__.'/Util/Legacy.php';
}

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
