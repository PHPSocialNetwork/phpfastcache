<?php

use Phpfastcache\Tests\Helper\TestHelper;

chdir(__DIR__ . '/../../');
require_once __DIR__ . '/../../vendor/autoload.php';
$testHelper = new TestHelper('PHP Lexer');


/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

function phpfastcache_read_dir($dir, $ext = null)
{
    $list = [];
    $dir .= '/';
    if (($res = opendir($dir)) === false) {
        exit(1);
    }
    while (($name = readdir($res)) !== false) {
        if ($name == '.' || $name == '..') {
            continue;
        }
        $name = $dir . $name;
        if (is_dir($name)) {
            $list = array_merge($list, phpfastcache_read_dir($name, $ext));
        } elseif (is_file($name)) {
            if (!is_null($ext) && substr(strrchr($name, '.'), 1) != $ext) {
                continue;
            }
            $list[] = $name;
        }
    }

    return $list;
}

$list = phpfastcache_read_dir('./lib', 'php');

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
