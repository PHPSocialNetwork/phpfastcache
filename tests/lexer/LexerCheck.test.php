<?php

use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__ . '/../../');
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('PHP Lexer');


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

$pfcReadDir = static function ($dir, $ext = null) use (&$pfcReadDir)
{
    $list = [[]];
    $dir .= '/';
    if (($res = opendir($dir)) === false) {
        exit(1);
    }
    while (($name = readdir($res)) !== false) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $name = $dir . $name;
        if (is_dir($name)) {
            $list[] = $pfcReadDir($name, $ext);
        } elseif (is_file($name)) {
            if (!is_null($ext) && substr(strrchr($name, '.'), 1) !== $ext) {
                continue;
            }
            $list[] = [$name];
        }
    }

    return array_merge(...$list);
};

$list = $pfcReadDir('./lib', 'php');

foreach (array_map('realpath', $list) as $file) {
    $output = '';
    \exec(($testHelper->isHHVM() ? 'hhvm' : 'php') . ' -l "' . $file . '"', $output, $status);

    $output = trim(implode("\n", $output));

    if ($status !== 0) {
        $testHelper->assertFail($output ?: "Syntax error found in {$file}");
    } else {
        $testHelper->assertPass($output ?: "No syntax errors detected in {$file}");
    }
}
$testHelper->terminateTest();
