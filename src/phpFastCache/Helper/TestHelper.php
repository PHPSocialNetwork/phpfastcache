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

namespace phpFastCache\Helper;

use phpFastCache\Api;

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
        $this->printText('[PhpFastCache API v' . Api::getVersion() . ']', true);
        $this->printText("[Begin Test: '{$testName}']");
        $this->printText('---');
    }

    /**
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * @return $this
     */
    public function resetExitCode()
    {
        $this->exitCode = 0;

        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function printSkipText($string)
    {
        $this->printText("[SKIP] {$string}");

        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function printPassText($string)
    {
        $this->printText("[PASS] {$string}");

        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function printFailText($string)
    {
        $this->printText("[FAIL] {$string}");
        $this->exitCode = 1;

        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function printNewLine($count = 1)
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
    public function printText($string, $strtoupper = false)
    {
        if (!$strtoupper) {
            print trim($string) . PHP_EOL;
        } else {
            print strtoupper(trim($string) . PHP_EOL);
        }

        return $this;
    }

    /**
     * @param string $cmd
     */
    public function runAsyncProcess($cmd)
    {
        if (substr(php_uname(), 0, 7) === 'Windows') {
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
        $this->runAsyncProcess('php ' . getcwd() . DIRECTORY_SEPARATOR . 'subprocess' . DIRECTORY_SEPARATOR . $file . '.subprocess' . $ext);
    }

    /**
     * @return void
     */
    public function terminateTest()
    {
        exit($this->exitCode);
    }

    /**
     * @param $obj
     * @param $prop
     * @return mixed
     * @throws \ReflectionException
     */
    public function accessInaccessibleMember($obj, $prop) {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

}