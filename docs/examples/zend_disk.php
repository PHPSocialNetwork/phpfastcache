<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Lucas Brucksch <support@hammermaps.de>
 *
 */
// Include composer autoloader
require __DIR__ . '/../../vendor/autoload.php';
// OR require_once("../src/phpFastCache/phpFastCache.php");
date_default_timezone_set("Europe/Paris");


use Phpfastcache\CacheManager;
use Phpfastcache\Core\phpFastCache;

// In your class, function, you can call the Cache
$InstanceCache = CacheManager::getInstance('zenddisk');
// OR $InstanceCache = CacheManager::getInstance() <-- open examples/global.setup.php to see more

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$CachedString = $InstanceCache->getItem($key);

if (is_null($CachedString->get())) {
    //$CachedString = "Zend Disk Cache --> Cache Enabled --> Well done !";
    // Write products to Cache in 10 minutes with same keyword
    $CachedString->set("Zend Disk Cache --> Cache Enabled --> Well done !");
    $InstanceCache->save($CachedString);

    echo "FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // ";
    echo $CachedString->get();

} else {
    echo "READ FROM CACHE // ";
    echo $CachedString->getExpirationDate()->format(Datetime::W3C);
    echo $CachedString->get();
}

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="./' . basename(__FILE__) . '">Reload</a>';