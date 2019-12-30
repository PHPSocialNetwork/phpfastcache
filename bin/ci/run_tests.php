<?php
/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
declare(strict_types=1);
require '../../vendor/autoload.php';

define('PFC_TEST_DIR', \realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tests'));

$climate = new League\CLImate\CLImate;
$status = 0;
$dir = __DIR__;
$driver = $argv[ 1 ] ?? 'Files';

/**
 * @param string $pattern
 * @param int $flags
 * @return array
 */
$globCallback = static function (string $pattern, int $flags = 0) use (&$globCallback): array
{
    $files = \glob($pattern, $flags);

    foreach (\glob(\dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = \array_merge($files, $globCallback($dir . '/' . \basename($pattern), $flags));
    }

    return $files;
};

foreach ($globCallback(PFC_TEST_DIR . DIRECTORY_SEPARATOR . '*.test.php') as $filename) {
    $climate->out('---');
    $command = "php -f {$filename} {$driver}";
   // echo "\e[33mphpfastcache@test \e[34m" . __DIR__ . " \e[92m# \e[91m" . $command . "\e[0m\n";
    $climate->out("<yellow>phpfastcache@unit-test</yellow> <blue>{$dir}</blue> <green>#</green> <red>$command</red>");
    \exec($command, $output, $return_var);
    echo "=====================================\n";
    echo \implode("\n", $output) . "\n";
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
    echo "\e[32m[OK] The build has passed successfully\e[0m\n\n";
} else {
    echo "\e[31m[KO] The build has failed miserably\e[0m\n\n";
}

exit($status);