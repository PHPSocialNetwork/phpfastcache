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
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 */

use phpFastCache\CacheManager;

// print_r(get_included_files());
// print_r(get_loaded_extensions());

// Include composer autoloader
require '../vendor/autoload.php';

CacheManager::setup(array(
    'ssdb' => array(
        'host' => '127.0.0.1',
        'port' => 8888,
        'password' => '',
        'timeout' => '',
    ),
));

$InstanceCache = CacheManager::getInstance('ssdb');

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$CachedString = $InstanceCache->get($key);

if (is_null($CachedString)) {
    $CachedString = "Ssdb Cache --> Cache Enabled --> Well done !";
    // Write products to Cache in 10 minutes with same keyword
    $InstanceCache->set($key, $CachedString, 600);

    echo "FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // ";
    echo $CachedString;

} else {
    echo "READ FROM CACHE // ";
    echo $CachedString;
}

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="/' . basename(__FILE__) . '">Reload</a>';
