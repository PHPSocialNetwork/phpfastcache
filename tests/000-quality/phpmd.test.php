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
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('Quality: PHPMD');

//
chdir(__DIR__ . '/../../');

$binary = realpath(getcwd() . '/vendor/bin/phpmd');

exec($binary . ' lib/ ansi phpmd.xml', $output, $resultCode);

if ($resultCode === 0) {
    $testHelper->assertPass('Great, PHPMD found no violation on the project');
} else {
    $testHelper->assertFail('Oh no, PHPMD found some violations on the project, full report available below:');
    $testHelper->printText(str_repeat('#', 100));
    $testHelper->printText(array_map(static fn ($str) => $str ? '    ' . $str : '', array_slice($output, 1)));
    $testHelper->printText(str_repeat('#', 100));
}

$testHelper->terminateTest();
