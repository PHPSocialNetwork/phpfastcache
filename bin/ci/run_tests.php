<?php
/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

define('PFC_TEST_DIR', \realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tests'));

$timestamp = microtime(true);
$climate = new League\CLImate\CLImate;
$climate->forceAnsiOn();
$phpBinPath = 'php ';
$status = 0;
$dir = __DIR__;
$driver = $argv[ 1 ] ?? 'Files';
$phpBinPath = $_SERVER['PHP_BIN_PATH'] ?? 'php';
$failedTests = [];

/**
 * @param string $pattern
 * @param int $flags
 * @return array
 */
$globCallback = static function (string $pattern, int $flags = 0) use (&$globCallback): array
{
    $files = \glob($pattern, $flags);
    $subFiles = [];

    foreach (\glob(\dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $subFiles[] = $globCallback($dir . '/' . \basename($pattern), $flags);
    }

    return \array_merge($files, ...$subFiles);
};

foreach ($globCallback(PFC_TEST_DIR . DIRECTORY_SEPARATOR . '*.test.php') as $filename) {
    $climate->backgroundLightYellow()->blue()->out('---');
    $command = "{$phpBinPath} -f {$filename} {$driver}";
    $climate->out("<yellow>phpfastcache@unit-tests</yellow> <blue>{$dir}</blue> <green>#</green> <red>$command</red>");

    \exec($command, $output, $return_var);
    $climate->out('=====================================');
    $climate->out(\implode("\n", $output));
    $climate->out('=====================================');
    if ($return_var === 0) {
        $climate->green("Process finished with exit code $return_var");
    } else {
        $climate->red("Process finished with exit code $return_var");
        $status = 255;
        $failedTests[] = basename($filename);
    }

    $climate->out('');
    /**
     * Reset $output to prevent override effects
     */
    unset($output);
}

$execTime = gmdate('i\m s\s', (int) round(microtime(true) - $timestamp, 3));
$climate->out('<yellow>Total tests duration: </yellow><light_green>' . $execTime . '</light_green>');

if ($status === 0) {
    $climate->backgroundGreen()->white()->flank('[OK] The build has passed successfully', '#')->out('');
} else {
    $climate->backgroundRed()->white()->flank('[KO] The build has failed miserably', '~')->out('');
    $climate->red()->out('Tests failed: ' . implode(', ', $failedTests))->out('');
}

exit($status);
