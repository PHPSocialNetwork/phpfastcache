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

namespace phpFastCache\plugins;

use phpFastCache\CacheManager;

// Setup your cronjob to run this file every
// 30 mins - 60 mins to help clean up
// the expired files faster

require_once (__DIR__ . "/../phpFastCache.php");

$setup = array(
    "path"  => "/your_path/to_clean/"
);

$cache = CacheManager::Files($setup);

// clean up expired files cache every hour
// For now only "files" drivers is supported
$cache->autoCleanExpired(3600*1);