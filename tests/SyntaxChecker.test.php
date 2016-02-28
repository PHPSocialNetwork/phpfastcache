<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
function read_dir($dir, $ext = null)
{
    $list = array();
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

$list = read_dir('.', 'php');
$list += read_dir('.', 'tpl');

$exit = 0;
foreach ($list as $file) {
    $output = '';

    exec('php -lf "' . $file . '"', $output, $status);

    if ($status != 0) {
        $exit = $status;
        echo '[FAIL]';
    }else{
        echo '[PASS]';
    }
    echo ' ' . implode("\n", $output);
    echo "\n";
}
exit($exit);