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

namespace Phpfastcache\Tests\Helper;

use League\CLImate\CLImate;
use Phpfastcache\Api;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use ReflectionClass;
use ReflectionException;
use Throwable;

use function sprintf;


/**
 * Class TestHelper
 * @package phpFastCache\Helper
 */
class TestHelper
{
    /***
     * @var int
     */
    protected $numOfFailedTests = 0;

    /**
     * @var int
     */
    protected $numOfPassedTests = 0;

    /**
     * @var int
     */
    protected $numOfSkippedTests = 0;

    /**
     * @var string
     */
    protected $testName;

    /**
     * @var int
     */
    protected $timestamp;

    /**
     * @var CLImate
     */
    protected $climate;

    /**
     * TestHelper constructor.
     *
     * @param string $testName
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheLogicException
     */
    public function __construct(string $testName)
    {
        $this->timestamp = microtime(true);
        $this->testName = $testName;
        $this->climate = new CLImate;
        $this->climate->forceAnsiOn();

        /**
         * Catch all uncaught exception
         * to our own exception handler
         */
        set_exception_handler([$this, 'exceptionHandler']);
        $this->setErrorHandler();

        $this->printHeaders();
    }

    protected function setErrorHandler($errorLevels = E_ALL)
    {
        set_error_handler([$this, 'errorHandler'], $errorLevels);
    }

    public function mutePhpNotices()
    {
        $errorLevels = E_ALL & ~E_NOTICE & ~E_USER_NOTICE;
        $this->setErrorHandler($errorLevels);
        error_reporting($errorLevels);
    }

    public function unmutePhpNotices()
    {
        $errorLevels = E_ALL;
        $this->setErrorHandler($errorLevels);
        error_reporting($errorLevels);
    }

    /**
     * @see https://stackoverflow.com/questions/933367/php-how-to-best-determine-if-the-current-invocation-is-from-cli-or-web-server
     * @return bool
     */
    public function isCli(): bool
    {
        if (defined('STDIN')) {
            return true;
        }

        if (php_sapi_name() === 'cli') {
            return true;
        }

        if (array_key_exists('SHELL', $_ENV)) {
            return true;
        }

        if (empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0) {
            return true;
        }

        if (!array_key_exists('REQUEST_METHOD', $_SERVER)) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isHHVM(): bool
    {
        return defined('HHVM_VERSION');
    }

    /**
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheLogicException
     */
    public function printHeaders()
    {
        if (!$this->isCli() && !headers_sent()) {
            header('Content-Type: text/plain, true');
        }

        $loadedExtensions = get_loaded_extensions();
        natcasesort($loadedExtensions);
        $this->printText("[<blue>Begin Test:</blue> <magenta>{$this->testName}</magenta>]");
        $this->printText('[<blue>PHPFASTCACHE:</blue> CORE <yellow>v' . Api::getPhpFastCacheVersion() . Api::getPhpFastCacheGitHeadHash() . '</yellow> | API <yellow>v' . Api::getVersion() . '</yellow>]');
        $this->printText('[<blue>PHP</blue> <yellow>v' . PHP_VERSION . '</yellow> with: <green>' . implode(', ', $loadedExtensions) . '</green>]');
        $this->printText('---');
    }

    /**
     * @param string $string
     * @param bool $strtoupper
     * @param string $prefix
     * @return $this
     */
    public function printText(string $string, bool $strtoupper = false, string $prefix = ''): self
    {
        if ($prefix) {
            $string = "[{$prefix}] {$string}";
        }
        if (!$strtoupper) {
            $this->climate->out(trim($string));
        } else {
            $this->climate->out(strtoupper(trim($string)));
        }

        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function printNoteText(string $string): self
    {
        $this->printText($string, false, '<blue>NOTE</blue>');

        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function printNewLine(int $count = 1): self
    {
        $this->climate->out(str_repeat(PHP_EOL, $count));
        return $this;
    }


    /**
     * @param string $string
     * @return $this
     */
    public function printDebugText(string $string): self
    {
        $this->printText($string, false, "\e[35mDEBUG\e[0m");

        return $this;
    }

    /**
     * @param string printFailText
     * @return $this
     */
    public function printInfoText(string $string): self
    {
        $this->printText($string, false, "\e[34mINFO\e[0m");

        return $this;
    }

    /**
     * @param string $file
     * @param string $ext
     */
    public function runSubProcess(string $file, string $ext = '.php')
    {
        $filePath =  getcwd() . DIRECTORY_SEPARATOR . 'subprocess' . DIRECTORY_SEPARATOR . $file . '.subprocess' . $ext;
        $binary = $this->isHHVM() ? 'hhvm' : 'php';
        $this->printDebugText(sprintf('Running %s subprocess on "%s"', \strtoupper($binary), $filePath));
        $this->runAsyncProcess("$binary $filePath");
    }

    /**
     * @param string $string
     * @param bool $failsTest
     * @return $this
     */
    public function assertFail(string $string, bool $failsTest = true): self
    {
        $this->printText($string, false, '<red>FAIL</red>');
        if ($failsTest) {
            $this->numOfFailedTests++;
        }

        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function assertPass(string $string): self
    {
        $this->printText($string, false, "\e[32mPASS\e[0m");
        $this->numOfPassedTests++;

        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function assertSkip(string $string): self
    {
        $this->printText($string, false, '<yellow>SKIP</yellow>');
        $this->numOfSkippedTests++;

        return $this;
    }


    /**
     * @return void
     */
    public function terminateTest()
    {
        $execTime = round(microtime(true) - $this->timestamp, 3);
        $totalCount = $this->numOfFailedTests + $this->numOfSkippedTests + $this->numOfPassedTests;

        $this->printText(
            sprintf(
                '<blue>Test results:</blue> <%1$s> %2$s %3$s failed</%1$s>, <%4$s>%5$s %6$s skipped</%4$s> and <%7$s>%8$s %9$s passed</%7$s> out of a total of %10$s %11$s.',
                $this->numOfFailedTests ? 'red' : 'green',
                $this->numOfFailedTests,
                ngettext('assertion', 'assertions', $this->numOfFailedTests),
                $this->numOfSkippedTests ? 'yellow' : 'green',
                $this->numOfSkippedTests,
                ngettext('assertion', 'assertions', $this->numOfSkippedTests),
                !$this->numOfPassedTests && $totalCount ? 'red' : 'green',
                $this->numOfPassedTests,
                ngettext('assertion', 'assertions', $this->numOfPassedTests),
                "<cyan>{$totalCount}</cyan>",
                ngettext('assertion', 'assertions', $totalCount),
            )
        );
        $this->printText('<blue>Test duration: </blue><yellow>' . $execTime . 's</yellow>');

        if($this->numOfFailedTests){
            exit(1);
        }

        if(!$this->numOfSkippedTests && $this->numOfPassedTests){
            exit(0);
        }

        exit(2);
    }

    /**
     * @param string $cmd
     */
    public function runAsyncProcess(string $cmd)
    {
        if (str_starts_with(php_uname(), 'Windows')) {
            pclose(popen('start /B ' . $cmd, 'r'));
        } else {
            exec($cmd . ' > /dev/null &');
        }
    }

    /**
     * @param $obj
     * @param $prop
     * @return mixed
     * @throws ReflectionException
     */
    public function accessInaccessibleMember($obj, $prop)
    {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     */
    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline)
    {
        $errorType = '';

        switch ($errno) {
            case E_PARSE:
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $errorType = '[FATAL ERROR]';
                break;
            case E_WARNING:
            case E_USER_WARNING:
            case E_COMPILE_WARNING:
            case E_RECOVERABLE_ERROR:
                $errorType = '[WARNING]';
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $errorType = '[NOTICE]';
                break;
            case E_STRICT:
                $errorType = '[STRICT]';
                break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $errorType = '[DEPRECATED]';
                break;
            default:
                break;
        }

        if ($errorType === '[FATAL ERROR]') {
            $this->assertFail(
                sprintf(
                    "<red>A critical error has been caught: \"%s\" in %s line %d</red>",
                    "$errorType $errstr",
                    $errfile,
                    $errline
                )
            );
        } else {
            $this->printDebugText(
                sprintf(
                    "<yellow>A non-critical error has been caught: \"%s\" in %s line %d</yellow>",
                    "$errorType $errstr",
                    $errfile,
                    $errline
                )
            );
        }
    }

    /**
     * @param EventManagerInterface $eventManager
     */
    public function debugEvents(EventManagerInterface $eventManager)
    {
        $eventManager->onEveryEvents(
            function (string $eventName) {
                $this->printDebugText("Triggered event '{$eventName}'");
            },
            'debugCallback'
        );
    }

    /**
     * @param ExtendedCacheItemPoolInterface $pool
     */
    public function runCRUDTests(ExtendedCacheItemPoolInterface $pool, bool $poolClear = true)
    {
        $this->printInfoText('Running CRUD tests on the following backend: ' . get_class($pool));

        if($poolClear){
            $this->printDebugText('Clearing backend before running test...');
            $pool->clear();
        }

        $cacheKey = 'cache_key_' . bin2hex(random_bytes(8) . '_' . random_int(100, 999));
        $cacheValue = 'cache_data_' . random_int(1000, 999999);
        $cacheTag = 'cache_tag_' . bin2hex(random_bytes(8) . '_' . random_int(100, 999));
        $cacheTag2 = 'cache_tag_' . bin2hex(random_bytes(8) . '_' . random_int(100, 999));
        $cacheItem = $pool->getItem($cacheKey);
        $this->printInfoText('Using cache key: ' . $cacheKey);

        $cacheItem->set($cacheValue)
            ->expiresAfter(60)
            ->addTags([$cacheTag, $cacheTag2]);

        if ($pool->save($cacheItem)) {
            $this->assertPass('The pool successfully saved an item.');
        } else {
            $this->assertFail('The pool failed to save an item.');
            return;
        }
        unset($cacheItem);
        $pool->detachAllItems();

        /**
         * Tag strategy ALL success and fail
         */

        $this->printInfoText('Re-fetching item <green>by its tags</green> <red>and an unknown tag</red> (tag strategy "<yellow>ALL</yellow>")...');
        $cacheItems = $pool->getItemsByTags([$cacheTag, $cacheTag2, 'unknown_tag'], $pool::TAG_STRATEGY_ALL);

        if (!isset($cacheItems[$cacheKey])) {
            $this->assertPass('The pool expectedly failed to retrieve the cache item.');
        } else {
            $this->assertFail('The pool unexpectedly retrieved the cache item.');
            return;
        }
        unset($cacheItems);
        $pool->detachAllItems();

        $this->printInfoText('Re-fetching item <green>by its tags</green> (tag strategy "<yellow>ALL</yellow>")...');
        $cacheItems = $pool->getItemsByTags([$cacheTag, $cacheTag2], $pool::TAG_STRATEGY_ALL);

        if (isset($cacheItems[$cacheKey])) {
            $this->assertPass('The pool successfully retrieved the cache item.');
        } else {
            $this->assertFail('The pool failed to retrieve the cache item.');
            return;
        }
        unset($cacheItems);
        $pool->detachAllItems();

        /**
         * Tag strategy ONLY success and fail
         */
        $this->printInfoText('Re-fetching item <green>by its tags</green> <red>and an unknown tag</red> (tag strategy "<yellow>ONLY</yellow>")...');
        $cacheItems = $pool->getItemsByTags([$cacheTag, $cacheTag2, 'unknown_tag'], $pool::TAG_STRATEGY_ONLY);

        if (!isset($cacheItems[$cacheKey])) {
            $this->assertPass('The pool expectedly failed to retrieve the cache item.');
        } else {
            $this->assertFail('The pool unexpectedly retrieved the cache item.');
            return;
        }
        unset($cacheItems);
        $pool->detachAllItems();

        $this->printInfoText('Re-fetching item <green>by one of its tags</green> (tag strategy "<yellow>ONLY</yellow>")...');
        $cacheItems = $pool->getItemsByTags([$cacheTag, $cacheTag2], $pool::TAG_STRATEGY_ONLY);

        if (isset($cacheItems[$cacheKey])) {
            $this->assertPass('The pool successfully retrieved the cache item.');
        } else {
            $this->assertFail('The pool failed to retrieve the cache item.');
            return;
        }
        unset($cacheItems);
        $pool->detachAllItems();

        /**
         * Tag strategy ONE success and fail
         */
        $this->printInfoText('Re-fetching item by one of its tags <red>and an unknown tag</red> (tag strategy "<yellow>ONE</yellow>")...');
        $cacheItems = $pool->getItemsByTags([$cacheTag, 'unknown_tag'], $pool::TAG_STRATEGY_ONE);

        if (isset($cacheItems[$cacheKey]) && $cacheItems[$cacheKey]->getKey() === $cacheKey) {
            $this->assertPass('The pool successfully retrieved the cache item.');
        } else {
            $this->assertFail('The pool failed to retrieve the cache item.');
            return;
        }
        $cacheItem = $cacheItems[$cacheKey];

        if ($cacheItem->get() === $cacheValue) {
            $this->assertPass('The pool successfully retrieved the expected value.');
        } else {
            $this->assertFail('The pool failed to retrieve the expected value.');
            return;
        }

        $this->printInfoText('Updating the cache item by appending some chars...');
        $cacheItem->append('_appended');
        $cacheValue .= '_appended';
        $pool->saveDeferred($cacheItem);
        $this->printInfoText('Deferred item is being committed...');
        if ($pool->commit()) {
            $this->assertPass('The pool successfully committed deferred cache item.');
        } else {
            $this->assertFail('The pool failed to commit deferred cache item.');
        }
        $pool->detachAllItems();
        unset($cacheItem);

        $cacheItem = $pool->getItem($cacheKey);
        if ($cacheItem->get() === $cacheValue) {
            $this->assertPass('The pool successfully retrieved the expected new value.');
        } else {
            $this->assertFail('The pool failed to retrieve the expected new value.');
            return;
        }
        if($poolClear){
            if ($pool->deleteItem($cacheKey)) {
                $this->assertPass('The pool successfully deleted the cache item.');
            } else {
                $this->assertFail('The pool failed to delete the cache item.');
            }

            if ($pool->clear()) {
                $this->assertPass('The pool successfully cleared.');
            } else {
                $this->assertFail('The cluster failed to clear.');
            }
            $pool->detachAllItems();
            unset($cacheItem);

            $cacheItem = $pool->getItem($cacheKey);
            if (!$cacheItem->isHit()) {
                $this->assertPass('The cache item does no longer exists in pool.');
            } else {
                $this->assertFail('The cache item still exists in pool.');
                return;
            }
        }

        $this->printInfoText(sprintf('I/O stats: %d HIT, %s MISS, %d WRITE', $pool->getIO()->getReadHit(), $pool->getIO()->getReadMiss(), $pool->getIO()->getWriteHit()));
        $this->printInfoText('<yellow>Driver info</yellow>: <magenta>' . $pool->getStats()->getInfo() . '</magenta>');
    }


    /**
     * @param Throwable $exception
     */
    public function exceptionHandler(Throwable $exception)
    {
        if ($exception instanceof PhpfastcacheDriverCheckException) {
            $this->assertSkip('A driver could not be initialized due to missing requirement: ' . $exception->getMessage());
        } else {
            $this->assertFail(
                sprintf(
                    'Uncaught exception "%s" in "%s" line %d with message: "%s"',
                    get_class($exception),
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getMessage()
                )
            );
        }
        $this->terminateTest();
    }
}
