<?php
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
declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

define('PFC_TEST_DIR', \realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tests'));

$timestamp = microtime(true);
$climate = new League\CLImate\CLImate;
$climate->forceAnsiOn();
$phpBinPath = 'php ';
$status = 0;
$dir = __DIR__;
$projectDir = dirname($dir, 2);
$driver = $argv[ 1 ] ?? 'Files';
$phpBinPath = $_SERVER['PHP_BIN_PATH'] ?? 'php';
$failedTests = [];
$skippedTests = [];

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

    return \array_merge(...$subFiles,  ...[$files]);
};

foreach ($globCallback(PFC_TEST_DIR . DIRECTORY_SEPARATOR . '*.test.php') as $filename) {
    $climate->backgroundLightYellow()->blue()->out('---');
    $command = "{$phpBinPath} -f {$filename} {$driver}";
    $shortCommand = str_replace(dirname(PFC_TEST_DIR), '~', $command);

    $climate->out("<yellow>phpfastcache@unit-tests</yellow> <blue>{$projectDir}</blue> <green>#</green> <red>$shortCommand</red>");

    \exec($command, $output, $return_var);
    $climate->out('=====================================');
    $climate->out(\implode("\n", $output));
    $climate->out('=====================================');
    if ($return_var === 0) {
        $climate->green("Test finished successfully");
    } else if($return_var === 2){
        $climate->yellow("Test skipped due to unmeet dependencies");
        $skippedTests[] = basename($filename);
    }else{
        $climate->red("Test finished with a least one error");
        $status = 1;
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

if (!$failedTests) {
    $climate->backgroundGreen()->white()->flank('[OK] The build has passed successfully', '#')->out('');
} else {
    $climate->backgroundRed()->white()->flank('[KO] The build has failed miserably', '~')->out('');
    $climate->red()->out('[TESTS FAILED] ' . PHP_EOL . '- '. implode(PHP_EOL . '- ', $failedTests))->out('');
}

if($skippedTests){
    $climate->yellow()->out('[TESTS SKIPPED] ' . PHP_EOL . '- '. implode(PHP_EOL . '- ', $skippedTests))->out('');
}

exit($status);
