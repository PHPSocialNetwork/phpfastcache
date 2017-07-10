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
// Include composer autoloader
require __DIR__ . '/../../vendor/autoload.php';
// OR require_once("../src/phpFastCache/phpFastCache.php");

use phpFastCache\CacheManager;

// Setup File Path on your config files
CacheManager::setDefaultConfig([
  "path" => sys_get_temp_dir()
]);

// In your class, function, you can call the Cache
$InstanceCache = CacheManager::getInstance('files');

$key = "product_page";
$key2 = "product_page2";

$cacheItem = $InstanceCache->getItem($key);
$cacheItem2 = $InstanceCache->getItem($key2);

$cacheItem->set('test')->expiresAfter(300);
$cacheItem2->set('test')->expiresAfter(300);

/**
 * Old way, but still working, to persist multiple items
 */
$InstanceCache->save($cacheItem);
$InstanceCache->save($cacheItem2);

/**
 * New way to persist multiple items
 * (Unlimited arguments)
 */
$InstanceCache->saveMultiple($cacheItem, $cacheItem2);

/**
 * New way to persist multiple items
 * Alternative for automated mass persisting
 */
/**
 * New way to persist multiple items
 * (Only first argument will be interpreted)
 */
$InstanceCache->saveMultiple([$cacheItem, $cacheItem2]);