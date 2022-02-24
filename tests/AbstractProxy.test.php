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

use Phpfastcache\Proxy\PhpfastcacheAbstractProxy;
use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('phpfastcacheAbstractProxy class');
$defaultDriver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');

$myCustomClass = new class($defaultDriver) extends PhpfastcacheAbstractProxy {};

$testHelper->runCRUDTests($myCustomClass);
$testHelper->terminateTest();
