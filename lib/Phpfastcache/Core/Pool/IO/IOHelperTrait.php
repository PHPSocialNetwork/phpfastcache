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

namespace Phpfastcache\Core\Pool\IO;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Drivers\Files\Config;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Util\Directory;


/**
 * Trait IOHelperTrait
 * @package phpFastCache\Core\Pool\IO
 * @property array $config The configuration array passed via DriverBaseTrait
 * @property ExtendedCacheItemInterface[] $itemInstances The item instance passed via CacheItemPoolTrait
 * @property EventManagerInterface $eventManager The event manager passed via CacheItemPoolTrait
 * @method Config getConfig() Return the config object
 * @method bool isPHPModule() Return true if is a php module
 * @method string getDriverName() Get the driver name
 */
trait IOHelperTrait
{
    /**
     * @var array
     */
    public $tmp = [];

    /**
     * Provide a generic getStats() method
     * for files-based drivers
     * @return DriverStatistic
     * @throws PhpfastcacheIOException
     */
    public function getStats(): DriverStatistic
    {
        $stat = new DriverStatistic();
        $path = $this->getFilePath(false);

        if (!is_dir($path)) {
            throw new PhpfastcacheIOException("Can't read PATH:" . $path);
        }
        $stat->setRawData(
                [
                    'tmp' => $this->tmp,
                ]
            )
            ->setSize(Directory::dirSize($path))
            ->setInfo('Number of files used to build the cache: ' . Directory::getFileCount($path));

        if($this->getConfig()->isUseStaticItemCaching()){
            $stat->setData(implode(', ', \array_keys($this->itemInstances)));
        }else{
            $stat->setData('No data available since static item caching option (useStaticItemCaching) is disabled.');
        }

        return $stat;
    }

    /**
     * @param $keyword
     * @param bool $skip
     * @return string
     * @throws PhpfastcacheIOException
     */
    protected function getFilePath($keyword, $skip = false): string
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
     */
    public function getPath($readonly = false): string
    {
        /**
         * Get the base system temporary directory
         */
        $tmp_dir = \rtrim(\ini_get('upload_tmp_dir') ?: \sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'phpfastcache';

        /**
         * Calculate the security key
         */
        {
            $securityKey = $this->getConfig()->getSecurityKey();
            if (!$securityKey || \mb_strtolower($securityKey) === 'auto') {
                if (isset($_SERVER['HTTP_HOST'])) {
                    $securityKey = \preg_replace('/^www./', '', \strtolower(\str_replace(':', '_', $_SERVER['HTTP_HOST'])));
                } else {
                    $securityKey = ($this->isPHPModule() ? 'web' : 'cli');
                }
            }

            if ($securityKey !== '') {
                $securityKey .= '/';
            }

            $securityKey = static::cleanFileName($securityKey);
        }

        /**
         * Extends the temporary directory
         * with the security key and the driver name
         */
        $tmp_dir = \rtrim($tmp_dir, '/') . DIRECTORY_SEPARATOR;

        if (empty($this->getConfig()->getPath())) {
            $path = $tmp_dir;
        } else {
            $path = \rtrim($this->getConfig()->getPath(), '/') . DIRECTORY_SEPARATOR;
        }

        $path_suffix = $securityKey . DIRECTORY_SEPARATOR . $this->getDriverName();
        $full_path = Directory::getAbsolutePath($path . $path_suffix);
        $full_path_tmp = Directory::getAbsolutePath($tmp_dir . $path_suffix);
        $full_path_hash = $this->getConfig()->getDefaultFileNameHashFunction()($full_path);

        /**
         * In readonly mode we only attempt
         * to verify if the directory exists
         * or not, if it does not then we
         * return the temp dir
         */
        if ($readonly === true) {
            if ($this->getConfig()->isAutoTmpFallback() && (!@\file_exists($full_path) || !@\is_writable($full_path))) {
                return $full_path_tmp;
            }
            return $full_path;
        }

        if (!isset($this->tmp[$full_path_hash]) || (!@\file_exists($full_path) || !@\is_writable($full_path))) {
            if (!@\file_exists($full_path)) {
                if (@mkdir($full_path, $this->getDefaultChmod(), true) === false && !\is_dir($full_path)) {
                    throw new PhpfastcacheIOException('The directory ' . $full_path . ' could not be created.');
                }
            } else {
                if (!@\is_writable($full_path)) {
                    if (!@\chmod($full_path, $this->getDefaultChmod()) && $this->getConfig()->isAutoTmpFallback()) {
                        /**
                         * Switch back to tmp dir
                         * again if the path is not writable
                         */
                        $full_path = $full_path_tmp;
                        if (!@\file_exists($full_path)) {
                            if (@\mkdir($full_path, $this->getDefaultChmod(), true) && !\is_dir($full_path)) {
                                throw new PhpfastcacheIOException('The directory ' . $full_path . ' could not be created.');
                            }
                        }
                    }
                }
            }

            /**
             * In case there is no directory
             * writable including the temporary
             * one, we must throw an exception
             */
            if (!@\file_exists($full_path) || !@\is_writable($full_path)) {
                throw new PhpfastcacheIOException(
                    'Path "' . $full_path . '" is not writable, please set a chmod 0777 or any writable permission and make sure to make use of an absolute path !'
                );
            }

            $this->tmp[$full_path_hash] = $full_path;
            $this->htaccessGen($full_path, $this->getConfig()->isValidOption('htaccess') ? $this->getConfig()->getHtaccess() : false);
        }

        return realpath($full_path);
    }

    /**
     * @param $filename
     * @return string
     */
    protected static function cleanFileName($filename): string
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
     * @param $path
     * @param bool $create
     * @throws PhpfastcacheIOException
     */
    protected function htaccessGen($path, $create = true)
    {
        if ($create === true) {
            if (!\is_writable($path)) {
                try {
                    if (!\chmod($path, 0777)) {
                        throw new PhpfastcacheIOException('Chmod failed on : ' . $path);
                    }
                } catch (PhpfastcacheIOException $e) {
                    throw new PhpfastcacheIOException('PLEASE CHMOD ' . $path . ' - 0777 OR ANY WRITABLE PERMISSION!', 0, $e);
                }
            }

            if (!\file_exists($path . '/.htaccess')) {
                $file = @\fopen($path . '/.htaccess', 'w+b');
                if (!$file) {
                    throw new PhpfastcacheIOException('PLEASE CHMOD ' . $path . ' - 0777 OR ANY WRITABLE PERMISSION!');
                }
                \fwrite(
                    $file,
                    <<<HTACCESS
### This .htaccess is auto-generated by PhpFastCache ###
<IfModule mod_authz_host>
Require all denied
</IfModule>
<IfModule !mod_authz_host>
Order Allow,Deny
Deny from all
</IfModule>
HTACCESS
                );
                \fclose($file);
            }
        }
    }

    /**
     * @param $keyword
     * @return string
     */
    protected function encodeFilename($keyword): string
    {
        return $this->getConfig()->getDefaultFileNameHashFunction()($keyword);
    }

    /**
     * @param $file
     * @return string
     * @throws PhpfastcacheIOException
     */
    protected function readFile($file): string
    {
        if (!\is_readable($file)) {
            throw new PhpfastcacheIOException("Cannot read file located at: {$file}");
        }
        if (\function_exists('file_get_contents')) {
            return (string)\file_get_contents($file);
        }

        $string = '';

        $file_handle = @\fopen($file, 'rb');
        while (!\feof($file_handle)) {
            $line = \fgets($file_handle);
            $string .= $line;
        }
        \fclose($file_handle);

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
     */
    protected function writefile(string $file, string $data, bool $secureFileManipulation = false): bool
    {
        /**
         * @eventName CacheWriteFileOnDisk
         * @param ExtendedCacheItemPoolInterface $this
         * @param string $file
         * @param bool $secureFileManipulation
         *
         */
        $this->eventManager->dispatch('CacheWriteFileOnDisk', $this, $file, $secureFileManipulation);

        if ($secureFileManipulation) {
            $tmpFilename = Directory::getAbsolutePath(
                dirname($file) . \DIRECTORY_SEPARATOR . 'tmp_' . $this->getConfig()->getDefaultFileNameHashFunction()(
                    \bin2hex(\random_bytes(16))
                )
            ) . '.' .  $this->getConfig()->getCacheFileExtension() . \random_int(1000, 9999);

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
