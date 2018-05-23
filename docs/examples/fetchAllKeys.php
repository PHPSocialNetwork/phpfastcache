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
use Phpfastcache\CacheManager;

// Include composer autoloader
require __DIR__ . '/../../vendor/autoload.php';

$InstanceCache = CacheManager::getInstance('files');
$InstanceCache->clear();

/**
 * @var $keys \Phpfastcache\Core\Item\ExtendedCacheItemInterface[]
 */
$keyPrefix = "product_page_";
$keys = [];

for ($i=1;$i<=10;$i++)
{
    $keys[$keyPrefix . $i] = $InstanceCache->getItem($keyPrefix . $i);
    if(!$keys[$keyPrefix . $i]->isHit()){
        $keys[$keyPrefix . $i]
          ->set(uniqid('pfc', true))
          ->addTag('pfc');
    }
}

$InstanceCache->saveMultiple($keys);

/**
 * Remove items references from memory
 */
unset($keys);
$InstanceCache->detachAllItems();
gc_collect_cycles();


/**
 * Now get the items by a specific tag
 */
$keys = $InstanceCache->getItemsByTag('pfc');
foreach ($keys as $key) {
    echo "Key: {$key->getKey()} =&gt; {$key->get()}<br />";
}

echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="./' . basename(__FILE__) . '">Reload</a>';
