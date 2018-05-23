<?php
/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
declare(strict_types=1);

define('PFC_TEST_DIR', \realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tests'));

/**
 * @param string $pattern
 * @param int $flags
 * @return array
 */
function phpfastcache_glob_recursive(string $pattern, int $flags = 0): array
{
    $files = \glob($pattern, $flags);

    foreach (\glob(\dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = \array_merge($files, phpfastcache_glob_recursive($dir . '/' . \basename($pattern), $flags));
    }

    return $files;
}

$status = 0;
$driver = $argv[ 1 ] ?? 'Files';

foreach (phpfastcache_glob_recursive(PFC_TEST_DIR . DIRECTORY_SEPARATOR . '*.test.php') as $filename) {
    echo "\e[97m--\n";
    $command = "php -f {$filename} {$driver}";
    echo "\e[33mphpfastcache@test \e[34m" . __DIR__ . " \e[92m# \e[91m" . $command . "\n";
    \exec($command, $output, $return_var);
    echo "=====================================\n";
    echo "\e[95m" . \implode("\n", $output) . "\n";
    echo "=====================================\n";
    if ($return_var === 0) {
        echo "\e[32mProcess finished with exit code $return_var";
    } else {
        echo "\e[31mProcess finished with exit code $return_var";
        $status = 255;
    }
    echo \str_repeat(PHP_EOL, 3);
    /**
     * Reset $output to prevent override effects
     */
    unset($output);
}

if ($status === 0) {
    echo "\e[32m[OK] The build has passed successfully";
} else {
    echo "\e[31m[KO] The build has failed miserably";
}

exit($status);