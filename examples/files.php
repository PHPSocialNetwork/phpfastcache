<?php
/**
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 */

// Include composer autoloader
require 'vendor/autoload.php';
// OR require_once("../src/phpFastCache/phpFastCache.php");
date_default_timezone_set("Europe/Paris");


use phpFastCache\CacheManager;
use phpFastCache\Core\phpFastCache;

// Setup File Path on your config files
phpFastCache::setup(array(
    "path" => '/var/www/phpfastcache.dev.geolim4.com/geolim4/tmp', // or in windows "C:/tmp/"
));

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
    $CachedString->set("Files Cache --> Cache Enabled --> Well done !")->expiresAfter(5);
	$InstanceCache->save($CachedString);

    echo "FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // ";
    echo $CachedString->get();

} else {
    echo "READ FROM CACHE // ";
	echo $CachedString->getExpirationDate()->format(Datetime::W3C);
    echo $CachedString->get();
}

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="./' . basename(__FILE__) . '">Reload</a>';