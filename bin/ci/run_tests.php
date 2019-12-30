<?php
/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

define('PFC_TEST_DIR', \realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tests'));

$climate = new League\CLImate\CLImate;
$climate->forceAnsiOn();
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
    $climate->backgroundLightYellow()->blue()->out('---');
    $command = "php -f {$filename} {$driver}";
    $climate->out("<yellow>phpfastcache@unit-test</yellow> <blue>{$dir}</blue> <green>#</green> <red>$command</red>");

    \exec($command, $output, $return_var);
    $climate->out('=====================================');
    $climate->out(\implode("\n", $output));
    $climate->out('=====================================');
    if ($return_var === 0) {
        $climate->green("Process finished with exit code $return_var");
    } else {
        $climate->red("Process finished with exit code $return_var");
        $status = 255;
    }

    $climate->out('');
    /**
     * Reset $output to prevent override effects
     */
    unset($output);
}

if ($status === 0) {
    $climate->green('[OK] The build has passed successfully');
} else {
    $climate->red('[KO] The build has failed miserably');
}

exit($status);