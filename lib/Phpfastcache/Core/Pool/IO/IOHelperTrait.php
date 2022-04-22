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

namespace Phpfastcache\Core\Pool\IO;

use Phpfastcache\Config\IOConfigurationOptionInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\Event;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Util\Directory;
use Phpfastcache\Util\SapiDetector;

/**
 * @method IOConfigurationOptionInterface getConfig()
 */
trait IOHelperTrait
{
    use TaggableCacheItemPoolTrait;

    /**
     * @var array<string, string>
     */
    public array $tmp = [];

    /**
     * Provide a generic getStats() method
     * for files-based drivers
     * @return DriverStatistic
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function getStats(): DriverStatistic
    {
        $stat = new DriverStatistic();
        $path = $this->getFilePath(false);

        if (!is_dir($path)) {
            throw new PhpfastcacheIOException("Can't read PATH:" . $path);
        }
        $stat->setSize(Directory::dirSize($path))
            ->setInfo('Number of files used to build the cache: ' . Directory::getFileCount($path))
            ->setRawData(
                [
                    'tmp' => $this->tmp,
                ]
            );

        if ($this->getConfig()->isUseStaticItemCaching()) {
            $stat->setData(implode(', ', \array_keys($this->itemInstances)));
        } else {
            $stat->setData('No data available since static item caching option (useStaticItemCaching) is disabled.');
        }

        return $stat;
    }

    /**
     * @param string|bool $keyword
     * @param bool $skip
     * @return string
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function getFilePath(string|bool $keyword, bool $skip = false): string
    {
        $path = $this->getPath();

        if ($keyword === false) {
            return $path;
        }

        $filename = $this->encodeFilename($keyword);
        $folder = \substr($filename, 0, 2) . DIRECTORY_SEPARATOR . \substr($filename, 2, 2);
        $path = \rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $folder;

        /**
         * Skip Create Sub Folders;
         */
        if (!$skip && !\is_dir($path) && @!\mkdir($path, $this->getDefaultChmod(), true) && !\is_dir($path)) {
            throw new PhpfastcacheIOException(
                'Path "' . $path . '" is not writable, please set a chmod 0777 or any writable permission and make sure to make use of an absolute path !'
            );
        }

        return $path . \DIRECTORY_SEPARATOR . $filename . '.' . $this->getConfig()->getCacheFileExtension();
    }

    /**
     * @param bool $readonly
     * @return string
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function getPath(bool $readonly = false): string
    {
        $tmpDir = \rtrim(\ini_get('upload_tmp_dir') ?: \sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'phpfastcache';
        $httpHost = $this->getConfig()->getSuperGlobalAccessor()('SERVER', 'HTTP_HOST');
        $securityKey = $this->buildSecurityKey($httpHost);

        /**
         * Extends the temporary directory
         * with the security key and the driver name
         */
        $tmpDir = \rtrim($tmpDir, '/') . DIRECTORY_SEPARATOR;

        if (empty($this->getConfig()->getPath())) {
            $path = $tmpDir;
        } else {
            $path = \rtrim($this->getConfig()->getPath(), '/') . DIRECTORY_SEPARATOR;
        }

        $pathSuffix = $securityKey . DIRECTORY_SEPARATOR . $this->getDriverName();
        $fullPath = Directory::getAbsolutePath($path . $pathSuffix);
        $fullPathTmp = Directory::getAbsolutePath($tmpDir . $pathSuffix);

        $this->mkdir($fullPath, $fullPathTmp);

        /**
         * In readonly mode we only attempt
         * to verify if the directory exists
         * or not, if it does not then we
         * return the temp dir
         */
        if ($readonly) {
            if ($this->getConfig()->isAutoTmpFallback() && (!@\file_exists($fullPath) || !@\is_writable($fullPath))) {
                return $fullPathTmp;
            }
            return $fullPath;
        }

        return realpath($fullPath);
    }

    protected function buildSecurityKey(?string $httpHost): string
    {
        $securityKey = $this->getConfig()->getSecurityKey();
        if (!$securityKey || \mb_strtolower($securityKey) === 'auto') {
            if (isset($httpHost)) {
                $securityKey = \preg_replace('/^www./', '', \strtolower(\str_replace(':', '_', $httpHost)));
            } else {
                $securityKey = (SapiDetector::isWebScript() ? 'web' : 'cli');
            }
        }

        if (!empty($securityKey)) {
            $securityKey .= '/';
        }

        return static::cleanFileName($securityKey);
    }

    /**
     * @throws PhpfastcacheIOException
     */
    protected function mkdir(string $fullPath, string $fullPathTmp): void
    {
        $fullPathHash = $this->getConfig()->getDefaultFileNameHashFunction()($fullPath);

        if (!isset($this->tmp[$fullPathHash]) || (!@\file_exists($fullPath) || !@\is_writable($fullPath))) {
            if (!@\file_exists($fullPath)) {
                if (@mkdir($fullPath, $this->getDefaultChmod(), true) === false && !\is_dir($fullPath)) {
                    throw new PhpfastcacheIOException('The directory ' . $fullPath . ' could not be created.');
                }
            } elseif (!@\is_writable($fullPath) && !@\chmod($fullPath, $this->getDefaultChmod()) && $this->getConfig()->isAutoTmpFallback()) {
                /**
                 * Switch back to tmp dir
                 * again if the path is not writable
                 */
                $fullPath = $fullPathTmp;
                if (!@\file_exists($fullPath) && @\mkdir($fullPath, $this->getDefaultChmod(), true) && !\is_dir($fullPath)) {
                    throw new PhpfastcacheIOException('The directory ' . $fullPath . ' could not be created.');
                }
            }

            /**
             * In case there is no directory
             * writable including the temporary
             * one, we must throw an exception
             */
            if (!@\file_exists($fullPath) || !@\is_writable($fullPath)) {
                throw new PhpfastcacheIOException(
                    'Path "' . $fullPath . '" is not writable, please set a chmod 0777 or any writable permission and make sure to make use of an absolute path !'
                );
            }

            $this->tmp[$fullPathHash] = $fullPath;
        }
    }

    /**
     * @param string $filename
     * @return string
     */
    protected static function cleanFileName(string $filename): string
    {
        $regex = [
            '/[\?\[\]\/\\\=\<\>\:\;\,\'\"\&\$\#\*\(\)\|\~\`\!\{\}]/',
            '/\.$/',
            '/^\./',
        ];
        $replace = ['-', '', ''];

        return \trim(\preg_replace($regex, $replace, \trim($filename)), '-');
    }

    /**
     * @return int
     */
    protected function getDefaultChmod(): int
    {
        if (!$this->getConfig()->getDefaultChmod()) {
            return 0777;
        }

        return $this->getConfig()->getDefaultChmod();
    }

    /**
     * @param string $keyword
     * @return string
     */
    protected function encodeFilename(string $keyword): string
    {
        return $this->getConfig()->getDefaultFileNameHashFunction()($keyword);
    }

    /**
     * @param string $file
     * @return string
     * @throws PhpfastcacheIOException
     */
    protected function readFile(string $file): string
    {
        if (!\is_readable($file)) {
            throw new PhpfastcacheIOException("Cannot read file located at: $file");
        }
        if (\function_exists('file_get_contents')) {
            return (string)\file_get_contents($file);
        }

        $string = '';

        $fileHandle = @\fopen($file, 'rb');
        while (!\feof($fileHandle)) {
            $line = \fgets($fileHandle);
            $string .= $line;
        }
        \fclose($fileHandle);

        return $string;
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @param string $file
     * @param string $data
     * @param bool $secureFileManipulation
     * @return bool
     * @throws PhpfastcacheIOException
     * @throws \Exception
     */
    protected function writeFile(string $file, string $data, bool $secureFileManipulation = false): bool
    {
        $this->eventManager->dispatch(Event::CACHE_WRITE_FILE_ON_DISK, $this, $file, $secureFileManipulation);

        if ($secureFileManipulation) {
            $tmpFilename = Directory::getAbsolutePath(
                dirname($file) . \DIRECTORY_SEPARATOR . 'tmp_' . $this->getConfig()->getDefaultFileNameHashFunction()(
                    \bin2hex(\random_bytes(16))
                )
            ) . '.' . $this->getConfig()->getCacheFileExtension() . \random_int(1000, 9999);

            $handle = \fopen($tmpFilename, 'w+b');
            if (\is_resource($handle)) {
                \flock($handle, \LOCK_EX);
                $octetWritten = fwrite($handle, $data);
                \flock($handle, \LOCK_UN);
                \fclose($handle);
            }

            if (!\rename($tmpFilename, $file)) {
                throw new PhpfastcacheIOException(\sprintf('Failed to rename %s to %s', $tmpFilename, $file));
            }
        } else {
            $handle = \fopen($file, 'w+b');
            if (\is_resource($handle)) {
                $octetWritten = \fwrite($handle, $data);
                \fclose($handle);
            }
        }

        return (bool)($octetWritten ?? false);
    }
}
