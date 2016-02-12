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

/**
 * This file ensure
 * a maximum compatibility
 * for user that do not
 * make use of composer
 */
use phpFastCache\CacheManager;

if (!defined('phpFastCache_LOADED_VIA_COMPOSER')) {
    require_once 'required_files.php';
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