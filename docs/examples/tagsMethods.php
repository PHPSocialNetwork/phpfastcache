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
require __DIR__ . '/../../vendor/autoload.php';

$InstanceCache = CacheManager::getInstance('apc');

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$key2 = "product_page2";
$CachedString = $InstanceCache->getItem($key);
$CachedString2 = $InstanceCache->getItem($key2);

if (is_null($CachedString->get()) || is_null($CachedString2->get())) {
    // Write products to Cache in 10 minutes with same keyword
    $CachedString->set("My beautifull Ios product")
      ->expiresAfter(600)
      ->addTag('Mobile')
      ->addTag('Ios');

    $CachedString2->set("My beautifull Android product")
      ->expiresAfter(600)
      ->addTag('Mobile')
      ->addTag('Android');

    $InstanceCache->save($CachedString);
    $InstanceCache->save($CachedString2);

    echo "FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // ";
    echo '<br />TAGS of product_page item: ' . $CachedString->getTagsAsString();
    echo '<br />TAGS of product_page2 item: ' . $CachedString2->getTagsAsString();


} else {
    echo "<br />READ FROM CACHE WITH TAGS 'Ios' // <br />";
    echo '<pre>';
    var_dump($InstanceCache->getItemsByTag('Ios'));// Will output product_page item
    echo '</pre>';

    echo "<br />READ FROM CACHE WITH TAGS 'Android' // <br />";
    echo '<pre>';
    var_dump($InstanceCache->getItemsByTag('Android'));// Will output product_page2 item
    echo '</pre>';

    echo "<br />READ FROM CACHE WITH TAGS 'Ios,Android' // <br />";
    echo '<pre>';
    var_dump($InstanceCache->getItemsByTags(['Ios', 'Android']));// Will output product_page and product_page2 item
    echo '</pre>';
}

/**
 * Finally you can delete by tags
 *
 * $InstanceCache->deleteItemsByTag('Ios');
 * $InstanceCache->deleteItemsByTags(['Ios', 'Android']);
 **/


echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="./' . basename(__FILE__) . '">Reload</a>';
