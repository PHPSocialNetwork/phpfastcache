<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('Driver list resolver');

$cache = CacheManager::getInstance('Redis');

for ($i=0; $i<10; $i++){
    $testHelper->printNoteText(sprintf('Running CRUD tests, loop %d/10', $i));
    $testHelper->runCRUDTests($cache);
}
$driverIO = $cache->getIO();

if($driverIO instanceof DriverIO && $driverIO->getReadHit() && $driverIO->getReadMiss() && $driverIO->getWriteHit()){
    $testHelper->assertPass('Driver IO entity returned some hit info.');
}else{
    $testHelper->assertFail('Driver IO entity did not returned some hit info as expected.');
}

$testHelper->terminateTest();
