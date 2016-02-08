<?php
/**
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpFastCache.com
 */

use phpFastCache\Core\InstanceManager;

// print_r(get_included_files());
// print_r(get_loaded_extensions());

// Include composer autoloader
require '../vendor/autoload.php';

$InstanceCache = InstanceManager::getInstance('ssdb');

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

    echo "Ssdb Cache --> Cached not enabled --> Reload page !";

} else {
    echo $CachedString;
}

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="/' . basename(__FILE__) . '">Reload</a>';
