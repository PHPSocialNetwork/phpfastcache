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

use Phpfastcache\Api;
use Phpfastcache\Exceptions\PhpfastcacheRootException;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('API class');

/**
 * Testing API version
 */
try {
    $version = Api::getVersion();
    $testHelper->assertPass(sprintf('Successfully retrieved the API version: %s', $version));
} catch (PhpfastcacheRootException $e) {
    $testHelper->assertFail(sprintf('Failed to retrieve the API version with the following error error: %s', $e->getMessage()));
}

/**
 * Testing PhpFastCache version
 */
try {
    $version = Api::getPhpFastCacheVersion();
    $testHelper->assertPass(sprintf('Successfully retrieved PhpFastCache version: %s', $version));
} catch (PhpfastcacheRootException $e) {
    $testHelper->assertFail(sprintf('Failed to retrieve the PhpFastCache version with the following error error: %s', $e->getMessage()));
}

/**
 * Testing API changelog
 */
try {
    $changelog = Api::getChangelog();
    $testHelper->assertPass(sprintf("Successfully retrieved API changelog:\n%s", $changelog));
} catch (PhpfastcacheRootException $e) {
    $testHelper->assertFail(sprintf('Failed to retrieve the API changelog with the following error error: %s', $e->getMessage()));
}

/**
 * Testing PhpFastCache changelog
 */
try {
    $changelog = Api::getPhpFastCacheChangelog();
    $testHelper->assertPass(sprintf("Successfully retrieved PhpFastCache changelog:\n%s", $changelog));
} catch (PhpfastcacheRootException $e) {
    $testHelper->assertFail(sprintf('Failed to retrieve the PhpFastCache changelog with the following error error: %s', $e->getMessage()));
}

$testHelper->terminateTest();
