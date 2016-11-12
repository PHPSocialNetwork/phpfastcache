<?php
/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

function glob_recursive($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);

    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
    {
        $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }

    return $files;
}

$status = 0;
$driver = (!empty($argv[1]) ? ucfirst($argv[1]) : 'Files');
$testDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tests');

foreach (glob_recursive($testDir . DIRECTORY_SEPARATOR . '*.test.php') as $filename) {
    echo "\e[97m--\n";
    $command = "php -f {$filename} {$driver}";
    echo "\e[33mphpfastcache@test \e[34m~ \e[92m# \e[91m" . $command . "\n";
    exec ($command, $output, $return_var);
    echo "=====================================\n";
    echo "\e[95m" . implode("\n", $output) . "\n";
    echo "=====================================\n";
    if($return_var === 0){
        echo "\e[32mProcess finished with exit code $return_var";
    }else{
        echo "\e[31mProcess finished with exit code $return_var";
        $status = 255;
    }
    echo "\n\n\n\n";
    /**
     * Reset $output to prevent override effects
     */
    unset($output);
}

exit($status);