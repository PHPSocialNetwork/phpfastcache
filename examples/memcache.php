<?php
/**
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 */

use phpFastCache\Core\CacheManager;

// Include composer autoloader
require '../vendor/autoload.php';

$InstanceCache = CacheManager::Memcache();
// OR CacheManager::getInstance('memcache');
/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$CachedString = $InstanceCache->get($key);

if (is_null($CachedString)) {
    $CachedString = "Memcache Cache --> Cache Enabled --> Well done !";
    // Write products to Cache in 10 minutes with same keyword
    $InstanceCache->set($key, $CachedString, 600);

    echo "Memcache Cache --> Cached not enabled --> Reload page !";

} else {
    echo $CachedString;
}

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="/' . basename(__FILE__) . '">Reload</a>';
