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

namespace phpFastCache\Core\Pool\IO;

use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\EventManager;
use phpFastCache\Exceptions\phpFastCacheIOException;
use phpFastCache\Util\Directory;

/**
 * Trait IOHelperTrait
 * @package phpFastCache\Core\Pool\IO
 * @property array $config The configuration array passed via DriverBaseTrait
 * @property ExtendedCacheItemInterface[] $itemInstances The item instance passed via CacheItemPoolTrait
 * @property EventManager $eventManager The event manager passed via CacheItemPoolTrait
 */
trait IOHelperTrait
{
    /**
     * @var array
     */
    public $tmp = [];

    /**
     * @param bool $readonly
     * @return string
     * @throws phpFastCacheIOException
     */
    public function getPath($readonly = false)
    {
        /**
         * Get the base system temporary directory
         */
        $tmp_dir = rtrim(ini_get('upload_tmp_dir') ?: sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'phpfastcache';

        /**
         * Calculate the security key
         */
        {
            $securityKey = array_key_exists('securityKey', $this->config) ? $this->config[ 'securityKey' ] : '';
            if (!$securityKey || $securityKey === 'auto') {
                if (isset($_SERVER[ 'HTTP_HOST' ])) {
                    $securityKey = preg_replace('/^www./', '', strtolower(str_replace(':', '_', $_SERVER[ 'HTTP_HOST' ])));
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
        $tmp_dir = rtrim($tmp_dir, '/') . DIRECTORY_SEPARATOR;

        if (empty($this->config[ 'path' ]) || !is_string($this->config[ 'path' ])) {
            $path = $tmp_dir;
        } else {
            $path = rtrim($this->config[ 'path' ], '/') . DIRECTORY_SEPARATOR;
        }

        $path_suffix = $securityKey . DIRECTORY_SEPARATOR . $this->getDriverName();
        $full_path = Directory::getAbsolutePath($path . $path_suffix);
        $full_path_tmp = Directory::getAbsolutePath($tmp_dir . $path_suffix);
        $full_path_hash = md5($full_path);

        /**
         * In readonly mode we only attempt
         * to verify if the directory exists
         * or not, if it does not then we
         * return the temp dir
         */
        if ($readonly === true) {
            if ($this->config[ 'autoTmpFallback' ] && (!@file_exists($full_path) || !@is_writable($full_path))) {
                return $full_path_tmp;
            }
            return $full_path;
        } else {
            if (!isset($this->tmp[ $full_path_hash ]) || (!@file_exists($full_path) || !@is_writable($full_path))) {
                if (!@file_exists($full_path)) {
                    @mkdir($full_path, $this->getDefaultChmod(), true);
                } else if (!@is_writable($full_path)) {
                    if (!@chmod($full_path, $this->getDefaultChmod()) && $this->config[ 'autoTmpFallback' ]) {
                        /**
                         * Switch back to tmp dir
                         * again if the path is not writable
                         */
                        $full_path = $full_path_tmp;
                        if (!@file_exists($full_path)) {
                            @mkdir($full_path, $this->getDefaultChmod(), true);
                        }
                    }
                }

                /**
                 * In case there is no directory
                 * writable including tye temporary
                 * one, we must throw an exception
                 */
                if (!@file_exists($full_path) || !@is_writable($full_path)) {
                    throw new phpFastCacheIOException('Path "' . $full_path . '" is not writable, please set a chmod 0777 or any writable permission and make sure to make use of an absolute path !');
                }

                $this->tmp[ $full_path_hash ] = $full_path;
                $this->htaccessGen($full_path, array_key_exists('htaccess', $this->config) ? $this->config[ 'htaccess' ] : false);
            }
        }

        return realpath($full_path);
    }


    /**
     * @param $keyword
     * @param bool $skip
     * @return string
     * @throws phpFastCacheIOException
     */
    protected function getFilePath($keyword, $skip = false)
    {
        $path = $this->getPath();

        if ($keyword === false) {
            return $path;
        }

        $filename = $this->encodeFilename($keyword);
        $folder = substr($filename, 0, 2) . DIRECTORY_SEPARATOR . substr($filename, 2, 2);
        $path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $folder;

        /**
         * Skip Create Sub Folders;
         */
        if (!$skip) {
            if (!file_exists($path)) {
                if (@!mkdir($path, $this->getDefaultChmod(), true)) {
                    throw new phpFastCacheIOException('PLEASE CHMOD ' . $path . ' - ' . $this->getDefaultChmod() . ' OR ANY WRITABLE PERMISSION!');
                }
            }
        }

        return $path . '/' . $filename . '.' . $this->config[ 'cacheFileExtension' ];
    }


    /**
     * @param $keyword
     * @return string
     */
    protected function encodeFilename($keyword)
    {
        return md5($keyword);
    }

    /**
     * @return int
     */
    protected function getDefaultChmod()
    {
        if (!isset($this->config[ 'default_chmod' ]) || $this->config[ 'default_chmod' ] == '' || is_null($this->config[ 'default_chmod' ])) {
            return 0777;
        } else {
            return $this->config[ 'default_chmod' ];
        }
    }

    /**
     * @param $filename
     * @return mixed
     */
    protected static function cleanFileName($filename)
    {
        $regex = [
          '/[\?\[\]\/\\\=\<\>\:\;\,\'\"\&\$\#\*\(\)\|\~\`\!\{\}]/',
          '/\.$/',
          '/^\./',
        ];
        $replace = ['-', '', ''];

        return trim(preg_replace($regex, $replace, trim($filename)), '-');
    }

    /**
     * @param $path
     * @param bool $create
     * @throws phpFastCacheIOException
     */
    protected function htaccessGen($path, $create = true)
    {
        if ($create === true) {
            if (!is_writable($path)) {
                try {
                    if (!chmod($path, 0777)) {
                        throw new phpFastCacheIOException('Chmod failed on : ' . $path);
                    }
                } catch (phpFastCacheIOException $e) {
                    throw new phpFastCacheIOException('PLEASE CHMOD ' . $path . ' - 0777 OR ANY WRITABLE PERMISSION!', 0, $e);
                }
            }

            if (!file_exists($path . "/.htaccess")) {
                $content = <<<HTACCESS
### This .htaccess is auto-generated by PhpFastCache ###
<IfModule mod_authz_host>
Require all denied
</IfModule>
<IfModule !mod_authz_host>
Order Allow,Deny
Deny from all
</IfModule>
HTACCESS;

                $file = @fopen($path . '/.htaccess', 'w+');
                if (!$file) {
                    throw new phpFastCacheIOException('PLEASE CHMOD ' . $path . ' - 0777 OR ANY WRITABLE PERMISSION!');
                }
                fwrite($file, $content);
                fclose($file);
            }
        }
    }


    /**
     * @param $file
     * @return string
     * @throws phpFastCacheIOException
     */
    protected function readfile($file)
    {
        if (function_exists('file_get_contents')) {
            return file_get_contents($file);
        } else {
            $string = '';

            $file_handle = @fopen($file, 'r');
            if (!$file_handle) {
                throw new phpFastCacheIOException("Cannot read file located at: {$file}");
            }
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                $string .= $line;
            }
            fclose($file_handle);

            return $string;
        }
    }

    /**
     * @param string $file
     * @param string $data
     * @param bool $secureFileManipulation
     * @return bool
     * @throws phpFastCacheIOException
     */
    protected function writefile($file, $data, $secureFileManipulation = false)
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
            $tmpFilename = Directory::getAbsolutePath(dirname($file) . '/tmp_' . md5(
                str_shuffle(uniqid($this->getDriverName(), false))
                . str_shuffle(uniqid($this->getDriverName(), false))
              ));

            $f = fopen($tmpFilename, 'w+');
            flock($f, LOCK_EX);
            $octetWritten = fwrite($f, $data);
            flock($f, LOCK_UN);
            fclose($f);

            if (!rename($tmpFilename, $file)) {
                throw new phpFastCacheIOException(sprintf('Failed to rename %s to %s', $tmpFilename, $file));
            }
        } else {
            $f = fopen($file, 'w+');
            $octetWritten = fwrite($f, $data);
            fclose($f);
        }

        return $octetWritten !== false;
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * Provide a generic getStats() method
     * for files-based drivers
     * @return DriverStatistic
     * @throws \phpFastCache\Exceptions\phpFastCacheIOException
     */
    public function getStats()
    {
        $stat = new DriverStatistic();
        $path = $this->getFilePath(false);

        if (!is_dir($path)) {
            throw new phpFastCacheIOException("Can't read PATH:" . $path);
        }

        $stat->setData(implode(', ', array_keys($this->itemInstances)))
          ->setRawData([
            'tmp' => $this->tmp,
          ])
          ->setSize(Directory::dirSize($path))
          ->setInfo('Number of files used to build the cache: ' . Directory::getFileCount($path));

        return $stat;
    }
}