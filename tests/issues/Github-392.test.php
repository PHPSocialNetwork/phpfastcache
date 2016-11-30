<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
use phpFastCache\CacheManager;

chdir(__DIR__);
require_once __DIR__ . '/../../src/autoload.php';

$status = 0;
echo "Testing Github issue #392 - Issue after calling removeTag\n";

$InstanceCache = CacheManager::getInstance('Files');

$CachedElement1 = $InstanceCache->getItem('el1');
$CachedElement1->set('element1')->expiresAfter(600);
$CachedElement1->addTags(array('tag_12'));
$InstanceCache->save($CachedElement1);
/**
 * var_dump($CachedElement1->getTags()); Outputs: tag_12
 */

$CachedElement1 = $InstanceCache->getItem('el1');
$CachedElement1->setTags(array('tag_34'));
$InstanceCache->save($CachedElement1);
/**
 * var_dump($CachedElement1->getTags()); Outputs: tag_34
 */

$CachedElement1 = $InstanceCache->getItem('el1');
$CachedElement1->removeTag('tag_12');

/**
 * Save after removing a non existing tag: works as expected
 */
try {
    $InstanceCache->save($CachedElement1);
    echo "[PASS] Save after removing a non existing tag: works as expected \n";
} catch (Exception $e) {
    $status = 255;
    echo '[FAIL] Save after removing a non existing tag failed with message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine() . "\n";
}
/**
 * var_dump($CachedElement1->getTags()); Outputs: tag_34
 */

$CachedElement1 = $InstanceCache->getItem('el1');
$CachedElement1->removeTags(array('tag_34'));

/**
 * Save after removing an existing tag: fails
 */
try {
    $InstanceCache->save($CachedElement1);
    echo "[PASS] Save after removing an existing tag \n";
} catch (Exception $e) {
    $status = 255;
    echo '[FAIL] Save after removing an existing tag failed with message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine() . "\n";
}
/**
 * var_dump($CachedElement1->getTags()); Outputs: empty
 */

exit($status);