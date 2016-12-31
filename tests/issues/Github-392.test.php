<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\CacheManager;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../src/autoload.php';
$testHelper = new TestHelper('Github issue #392 - Issue after calling removeTag');

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
    $testHelper->printPassText('Save after removing a non existing tag: works as expected');
} catch (Exception $e) {
    $testHelper->printFailText('Save after removing a non existing tag failed with message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine());
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
    $testHelper->printPassText('Save after removing an existing tag');
} catch (Exception $e) {
    $testHelper->printFailText('Save after removing an existing tag failed with message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine());
}
/**
 * var_dump($CachedElement1->getTags()); Outputs: empty
 */

$testHelper->terminateTest();