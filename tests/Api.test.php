<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use phpFastCache\Api;
use phpFastCache\Exceptions\phpFastCacheRootException;
use phpFastCache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('API class');

/**
 * Testing API version
 */
try {
    $version = Api::getVersion();
    $testHelper->printPassText(sprintf('Successfully retrieved the API version: %s', $version));
} catch (phpFastCacheRootException $e) {
    $testHelper->printFailText(sprintf('Failed to retrieve the API version with the following error error: %s', $e->getMessage()));
}

/**
 * Testing PhpFastCache version
 */
try {
    $version = Api::getPhpFastCacheVersion();
    $testHelper->printPassText(sprintf('Successfully retrieved PhpFastCache version: %s', $version));
} catch (phpFastCacheRootException $e) {
    $testHelper->printFailText(sprintf('Failed to retrieve the PhpFastCache version with the following error error: %s', $e->getMessage()));
}

/**
 * Testing API changelog
 */
try {
    $changelog = Api::getChangelog();
    $testHelper->printPassText(sprintf("Successfully retrieved API changelog:\n%s", $changelog));
} catch (phpFastCacheRootException $e) {
    $testHelper->printFailText(sprintf('Failed to retrieve the API changelog with the following error error: %s', $e->getMessage()));
}

/**
 * Testing PhpFastCache changelog
 */
try {
    $changelog = Api::getPhpFastCacheChangelog();
    $testHelper->printPassText(sprintf("Successfully retrieved PhpFastCache changelog:\n%s", $changelog));
} catch (phpFastCacheRootException $e) {
    $testHelper->printFailText(sprintf('Failed to retrieve the PhpFastCache changelog with the following error error: %s', $e->getMessage()));
}

$testHelper->terminateTest();
