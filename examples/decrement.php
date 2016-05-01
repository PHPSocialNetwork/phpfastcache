<?php
/**
 * Welcome to Learn Lesson
 * This is very Simple PHP Code of Caching
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 */

// Include composer autoloader
require __DIR__ . '/../vendor/autoload.php';
// OR require_once("../src/phpFastCache/phpFastCache.php");
date_default_timezone_set("Europe/Paris");


use phpFastCache\CacheManager;
use phpFastCache\Core\phpFastCache;

// Setup File Path on your config files
CacheManager::setup(array(
    "path" => '/var/www/phpfastcache.dev.geolim4.com/geolim4/tmp', // or in windows "C:/tmp/"
));

// In your class, function, you can call the Cache
$InstanceCache = CacheManager::getInstance('redis');

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_decrement";
$CachedString = $InstanceCache->getItem($key);
if (is_null($CachedString->get())) {
	$CachedString->set(1000)->expiresAfter(10);
	
    echo "FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // DECREMENT // ";
    echo $CachedString->decrement()->get();

} else {
    echo "READ FROM CACHE // decrement // ";
    echo $CachedString->decrement()->get();
}

$InstanceCache->save($CachedString);

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="./' . basename(__FILE__) . '">Reload</a>';