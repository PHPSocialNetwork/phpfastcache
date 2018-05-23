<?php
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
// Include composer autoloader
require __DIR__ . '/../../vendor/autoload.php';
// OR require_once("../src/phpFastCache/phpFastCache.php");
date_default_timezone_set("Europe/Paris");


use Phpfastcache\CacheManager;

// Setup File Path on your config files
CacheManager::setDefaultConfig([
  "path" => sys_get_temp_dir(),
  "itemDetailedDate" => true
]);

// In your class, function, you can call the Cache
$InstanceCache = CacheManager::getInstance('files');
// OR $InstanceCache = CacheManager::getInstance() <-- open examples/global.setup.php to see more

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$CachedString = $InstanceCache->getItem($key);

if (is_null($CachedString->get())) {
    //$CachedString = "Files Cache --> Cache Enabled --> Well done !";
    // Write products to Cache in 10 minutes with same keyword
    $CachedString->set("Files Cache --> Cache Enabled --> Well done !")->expiresAfter(60);
    $InstanceCache->save($CachedString);

    echo "FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // ";
    echo $CachedString->get();

} else {
    $CachedString->set("Files Cache --> Cache Enabled --> Well done !");
    $InstanceCache->save($CachedString);

    echo "READ FROM CACHE // ";
    echo "\n CREATION DATE: " . $CachedString->getCreationDate()->format(DateTime::W3C);
    echo "\n MODIFICATION DATE: " . $CachedString->getModificationDate()->format(DateTime::W3C);
    echo "\n EXPIRATION DATE: " . $CachedString->getExpirationDate()->format(DateTime::W3C);
    echo $CachedString->get();
}

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="./' . basename(__FILE__) . '">Reload</a>';