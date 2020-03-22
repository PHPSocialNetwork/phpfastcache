<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Helper;

use Phpfastcache\Api;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use ReflectionClass;
use ReflectionException;
use Throwable;


/**
 * Class TestHelper
 * @package phpFastCache\Helper
 */
class TestHelper
{
    /**
     * @var string
     */
    protected $testName;

    /**
     * @var int
     */
    protected $exitCode = 0;

    /**
     * @var int
     */
    protected $timestamp;

    /**
     * @var \League\CLImate\CLImate
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
        $this->climate = new \League\CLImate\CLImate;
        $this->climate->forceAnsiOn();

        /**
         * Catch all uncaught exception
         * to our own exception handler
         */
        set_exception_handler([$this, 'exceptionHandler']);
        set_error_handler([$this, 'errorHandler']);

        $this->printHeaders();
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
        $this->printText('[PhpFastCache CORE v' . Api::getPhpFastCacheVersion() . Api::getPhpFastCacheGitHeadHash() . ']', true);
        $this->printText('[PhpFastCache API v' . Api::getVersion() . ']', true);
        $this->printText('[PHP v' . PHP_VERSION . ' with: ' . implode(', ', $loadedExtensions) . ']', true);
        $this->printText("[Begin Test: '{$this->testName}']");
        $this->printText('---');
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
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * @return $this
     */
    public function resetExitCode(): self
    {
        $this->exitCode = 0;

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
     * @param string $file
     * @param string $ext
     */
    public function runSubProcess(string $file, string $ext = '.php')
    {
        $this->runAsyncProcess(($this->isHHVM() ? 'hhvm ' : 'php ') . getcwd() . DIRECTORY_SEPARATOR . 'subprocess' . DIRECTORY_SEPARATOR . $file . '.subprocess' . $ext);
    }

    /**
     * @param string $cmd
     */
    public function runAsyncProcess(string $cmd)
    {
        if (substr(php_uname(), 0, 7) === 'Windows') {
            pclose(popen('start /B ' . $cmd, 'r'));
        } else {
            exec($cmd . ' > /dev/null &');
        }
    }

    /**
     * @return bool
     */
    public function isHHVM(): bool
    {
        return defined('HHVM_VERSION');
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
     * @param Throwable $exception
     */
    public function exceptionHandler(Throwable $exception)
    {
        if ($exception instanceof PhpfastcacheDriverCheckException) {
            $this->printSkipText('A driver could not be initialized due to missing requirement: ' . $exception->getMessage());
            $this->exitCode = 0;
        } else {
            $this->printFailText(
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

    /**
     * @param string $string
     * @return $this
     */
    public function printSkipText(string $string): self
    {
        $this->printText($string, false, '<yellow>SKIP</yellow>');

        return $this;
    }

    /**
     * @param string $string
     * @param bool $failsTest
     * @return $this
     */
    public function printFailText(string $string, bool $failsTest = true): self
    {
        $this->printText($string, false, '<red>FAIL</red>');
        if ($failsTest) {
            $this->exitCode = 1;
        }

        return $this;
    }

    /**
     * @return void
     */
    public function terminateTest()
    {
        $execTime = round(microtime(true) - $this->timestamp, 3);

        $this->printText('<yellow>Test duration: </yellow><light_green>' . $execTime . 's</light_green>');
        exit($this->exitCode);
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
            $this->printFailText(
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
     * @param string $string
     * @return $this
     */
    public function printDebugText(string $string): self
    {
        $this->printText($string, false, "\e[35mDEBUG\e[0m");

        return $this;
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
    public function runCRUDTests(ExtendedCacheItemPoolInterface $pool)
    {
        $this->printInfoText('Running CRUD tests on the following backend: ' . get_class($pool));
        $pool->clear();

        $cacheKey = 'cache_key_' . bin2hex(random_bytes(8) . '_' . random_int(100, 999));
        $cacheValue = 'cache_data_' . random_int(1000, 999999);
        $cacheTag = 'cache_tag_' . bin2hex(random_bytes(8) . '_' . random_int(100, 999));
        $cacheItem = $pool->getItem($cacheKey);
        $this->printInfoText('Using cache key: ' . $cacheKey);

        $cacheItem->set($cacheValue)
            ->expiresAfter(600)
            ->addTag($cacheTag);

        if ($pool->save($cacheItem)) {
            $this->printPassText('The pool successfully saved an item.');
        } else {
            $this->printFailText('The pool failed to save an item.');
            return;
        }

        /***
         * Detach the items to force "re-pull" from the backend
         */
        $pool->detachAllItems();

        $this->printInfoText('Re-fetching item by its tag...');
        $cacheItems = $pool->getItemsByTag($cacheTag);

        if (isset($cacheItems[$cacheKey]) && $cacheItems[$cacheKey]->getKey() === $cacheKey) {
            $this->printPassText('The pool successfully retrieved the cache item.');
        } else {
            $this->printFailText('The pool failed to retrieve the cache item.');
            return;
        }
        $cacheItem = $cacheItems[$cacheKey];

        if ($cacheItem->get() === $cacheValue) {
            $this->printPassText('The pool successfully retrieved the expected value.');
        } else {
            $this->printFailText('The pool failed to retrieve the expected value.');
            return;
        }

        $this->printInfoText('Updating the cache item by appending some chars...');
        $cacheItem->append('_appended');
        $cacheValue .= '_appended';
        $pool->saveDeferred($cacheItem);
        $this->printInfoText('Deferred item is being committed...');
        if ($pool->commit()) {
            $this->printPassText('The pool successfully committed deferred cache item.');
        } else {
            $this->printFailText('The pool failed to commit deferred cache item.');
        }

        /***
         * Detach the items to force "re-pull" from the backend
         */
        $pool->detachAllItems();
        unset($cacheItem);
        $cacheItem = $pool->getItem($cacheKey);

        if ($cacheItem->get() === $cacheValue) {
            $this->printPassText('The pool successfully retrieved the expected new value.');
        } else {
            $this->printFailText('The pool failed to retrieve the expected new value.');
            return;
        }


        if ($pool->deleteItem($cacheKey)) {
            $this->printPassText('The pool successfully deleted the cache item.');
        } else {
            $this->printFailText('The pool failed to delete the cache item.');
        }

        if ($pool->clear()) {
            $this->printPassText('The pool successfully cleared.');
        } else {
            $this->printFailText('The cluster failed to clear.');
        }

        $this->printInfoText(sprintf('I/O stats: %d HIT, %s MISS, %d WRITE', $pool->getIO()->getReadHit(), $pool->getIO()->getReadMiss(), $pool->getIO()->getWriteHit()));
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
     * @param string $string
     * @return $this
     */
    public function printPassText(string $string): self
    {
        $this->printText($string, false, "\e[32mPASS\e[0m");


        return $this;
    }
}