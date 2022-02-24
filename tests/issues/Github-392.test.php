<?php

declare(strict_types=1);

/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Github issue #392 - Issue after calling removeTag');

$InstanceCache = CacheManager::getInstance('Files');

$CachedElement1 = $InstanceCache->getItem('el1');
$CachedElement1->set('element1')->expiresAfter(600);
$CachedElement1->addTags(['tag_12']);
$InstanceCache->save($CachedElement1);
/**
 * var_dump($CachedElement1->getTags()); Outputs: tag_12
 */

$CachedElement1 = $InstanceCache->getItem('el1');
$CachedElement1->setTags(['tag_34']);
$InstanceCache->save($CachedElement1);
/**
 * var_dump($CachedElement1->getTags()); Outputs: tag_34
 */

$CachedElement1 = $InstanceCache->getItem('el1');
$CachedElement1->removeTag('tag_12');

/*
 * Save after removing a non existing tag: works as expected
 */
try {
    $InstanceCache->save($CachedElement1);
    $testHelper->assertPass('Save after removing a non existing tag: works as expected');
} catch (Exception $e) {
    $testHelper->assertFail('Save after removing a non existing tag failed with message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine());
}
/**
 * var_dump($CachedElement1->getTags()); Outputs: tag_34
 */

$CachedElement1 = $InstanceCache->getItem('el1');
$CachedElement1->removeTags(['tag_34']);

/*
 * Save after removing an existing tag: fails
 */
try {
    $InstanceCache->save($CachedElement1);
    $testHelper->assertPass('Save after removing an existing tag');
} catch (Exception $e) {
    $testHelper->assertFail('Save after removing an existing tag failed with message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine());
}
/*
 * var_dump($CachedElement1->getTags()); Outputs: empty
 */

$testHelper->terminateTest();
