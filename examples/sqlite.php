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

// Setup Sqlite Path on your config files
phpFastCache::setup(array(
    "path" => '/var/www/phpfastcache.dev.geolim4.com/geolim4/tmp', // or in windows "C:/tmp/"
));

// In your class, function, you can call the Cache
$InstanceCache = CacheManager::getInstance('sqlite');
// OR $InstanceCache = CacheManager::getInstance() <-- open examples/global.setup.php to see more

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$CachedString = $InstanceCache->getItem($key);
$CachedString2 = $InstanceCache->getItem($key.'2');

if (is_null($CachedString->get()) || is_null($CachedString2->get())) {
    //$CachedString = "Sqlite Cache --> Cache Enabled --> Well done !";
    // Write products to Cache in 10 minutes with same keyword
    $CachedString->set("Sqlite Cache --> Cache Enabled --> Well done !")->expiresAfter(5);
    $CachedString2->set("Sqlite Cache2 --> Cache Enabled --> Well done !")->expiresAfter(5);
	$InstanceCache->save($CachedString);
	$InstanceCache->save($CachedString2);

    echo "FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // ";
	echo '<br>';
    echo $CachedString->get();
	echo '<br>';
    echo $CachedString2->get();

} else {
    echo "READ FROM CACHE // ";
	//echo $CachedString->getExpirationDate()->format(Datetime::W3C);
    echo $CachedString->get();
	echo '<br>';
	echo "READ FROM CACHE // ";
    echo $CachedString2->get();
}

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="./' . basename(__FILE__) . '">Reload</a>';