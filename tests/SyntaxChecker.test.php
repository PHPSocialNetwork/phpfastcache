<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

function read_dir($dir, $ext = null)
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
            $list = array_merge($list, read_dir($name, $ext));
        } elseif (is_file($name)) {
            if (!is_null($ext) && substr(strrchr($name, '.'), 1) != $ext) {
                continue;
            }
            $list[] = $name;
        }
    }

    return $list;
}

$list = read_dir(__DIR__ . '/../', 'php');
$list += read_dir(__DIR__ . '/../', 'tpl');

$exit = 0;
foreach ($list as $file) {
    $output = '';
    /**
     * @todo Make the exclusions much cleaner
     */
    if (strpos($file, '/vendor/composer') === false && strpos($file, '/bin/stubs') === false) {
        exec('php -l "' . $file . '"', $output, $status);
    } else {
        echo '[SKIP] ' . $file;
        echo "\n";
        continue;
    }

    if ($status !== 0) {
        $exit = $status;
        echo '[FAIL]';
    } else {
        echo '[PASS]';
    }
    echo ' ' . implode("\n", $output);
    echo "\n";
}
exit($exit);