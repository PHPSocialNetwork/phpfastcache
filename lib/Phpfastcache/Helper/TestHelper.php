<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Helper;

use Phpfastcache\Api;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;

/**
 * Class TestHelper
 * @package phpFastCache\Helper
 */
class TestHelper
{
    /**
     * @var int
     */
    protected $exitCode = 0;

    /**
     * TestHelper constructor.
     * @param $testName
     */
    public function __construct($testName)
    {
        $this->printText('[PhpFastCache CORE v' . Api::getPhpFastCacheVersion() . Api::getPhpFastCacheGitHeadHash() . ']', true);
        $this->printText('[PhpFastCache API v' . Api::getVersion() . ']', true);
        $this->printText('[PHP v' . PHP_VERSION . ']', true);
        $this->printText("[Begin Test: '{$testName}']");
        $this->printText('---');

        /**
         * Catch all uncaught exception
         * to our own exception handler
         */
        set_exception_handler([$this, 'exceptionHandler']);
        set_error_handler([$this, 'errorHandler']);
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
    public function printSkipText($string): self
    {
        $this->printText("[SKIP] {$string}");

        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function printPassText($string): self
    {
        $this->printText("[PASS] {$string}");

        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function printInfoText($string): self
    {
        $this->printText("[INFO] {$string}");

        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function printDebugText($string): self
    {
        $this->printText("[DEBUG] {$string}");

        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function printFailText($string): self
    {
        $this->printText("[FAIL] {$string}");
        $this->exitCode = 1;

        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function printNewLine($count = 1): self
    {
        for ($i = 0; $i < $count; $i++) {
            print PHP_EOL;
        }

        return $this;
    }

    /**
     * @param $string
     * @param bool $strtoupper
     * @return $this
     */
    public function printText($string, $strtoupper = false): self
    {
        if (!$strtoupper) {
            print \trim($string) . PHP_EOL;
        } else {
            print strtoupper(\trim($string) . PHP_EOL);
        }

        return $this;
    }

    /**
     * @param string $cmd
     */
    public function runAsyncProcess($cmd)
    {
        if (\substr(php_uname(), 0, 7) === 'Windows') {
            pclose(popen('start /B ' . $cmd, 'r'));
        } else {
            exec($cmd . ' > /dev/null &');
        }
    }

    /**
     * @param string $file
     * @param string $ext
     */
    public function runSubProcess($file, $ext = '.php')
    {
        $this->runAsyncProcess(($this->isHHVM() ? 'hhvm ' : 'php ') . getcwd() . DIRECTORY_SEPARATOR . 'subprocess' . DIRECTORY_SEPARATOR . $file . '.subprocess' . $ext);
    }

    /**
     * @return void
     */
    public function terminateTest()
    {
        exit($this->exitCode);
    }

    /**
     * @return bool
     */
    public function isHHVM(): bool
    {
        return \defined('HHVM_VERSION');
    }

    /**
     * @param $obj
     * @param $prop
     * @return mixed
     * @throws \ReflectionException
     */
    public function accessInaccessibleMember($obj, $prop)
    {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * @param \Throwable $exception
     */
    public function exceptionHandler(\Throwable $exception)
    {
        if ($exception instanceof PhpfastcacheDriverCheckException) {
            $this->printSkipText('A driver could not be initialized due to missing requirement: ' . $exception->getMessage());
        } else {
            $this->printFailText(sprintf(
              'Uncaught exception "%s" in "%s" line %d with message: "%s"',
              \get_class($exception),
              $exception->getFile(),
              $exception->getLine(),
              $exception->getMessage()
            ));
        }
        $this->terminateTest();
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
            default :
                break;
        }

        if ($errorType === '[FATAL ERROR]') {
            $this->printFailText(sprintf(
              "A critical error has been caught: \"%s\" in %s line %d",
              "$errorType $errstr",
              $errfile,
              $errline
            ));
        } else {
            $this->printDebugText(sprintf(
              "A non-critical error has been caught: \"%s\" in %s line %d",
              "$errorType $errstr",
              $errfile,
              $errline
            ));
        }
    }
}