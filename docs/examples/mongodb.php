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

use phpFastCache\CacheManager;

// Include composer autoloader
require __DIR__ . '/../vendor/autoload.php';

$InstanceCache = CacheManager::getInstance('mongodb', [
  'host' => '127.0.0.1',
  'port' => '27017',
  'username' => '',
  'password' => '',
  'timeout' => '1',
]);


/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$CachedString = $InstanceCache->getItem($key);
/* var_dump($CachedString->get());
exit; */
if (is_null($CachedString->get())) {
    // Write products to Cache in 10 minutes with same keyword
    $CachedString->set("Mongodb Cache --> Cache Enabled --> Well done !")->expiresAfter(5);
    $InstanceCache->save($CachedString);

    echo "FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // ";
    echo $CachedString->get();

} else {
    echo "READ FROM CACHE // ";
    echo $CachedString->get();
}

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="./' . basename(__FILE__) . '">Reload</a>';
