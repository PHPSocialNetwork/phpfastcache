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

namespace phpFastCache\Core;

use phpFastCache\Exceptions\phpFastCacheCoreException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Util\Directory;

/**
 * Trait PathSeekerTrait
 * @package phpFastCache\Core\Pool\IO
 * @property array $config The configuration array passed via DriverBaseTrait
 */
trait PathSeekerTrait
{
    /**
     * @var array
     */
    public $tmp = [];

    /**
     * @param bool $readonly
     * @return string
     * @throws phpFastCacheDriverException
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
            if(!@file_exists($full_path) || !@is_writable($full_path)){
                return $full_path_tmp;
            }
            return $full_path;
        }else{
            if (!isset($this->tmp[ $full_path_hash ]) || (!@file_exists($full_path) || !@is_writable($full_path))) {
                if (!@file_exists($full_path)) {
                    @mkdir($full_path, $this->setChmodAuto(), true);
                }elseif (!@is_writable($full_path)) {
                    if (!@chmod($full_path, $this->setChmodAuto()))
                    {
                        /**
                         * Switch back to tmp dir
                         * again if the path is not writable
                         */
                        $full_path = $full_path_tmp;
                        if (!@file_exists($full_path)) {
                            @mkdir($full_path, $this->setChmodAuto(), true);
                        }
                    }
                }

                /**
                 * In case there is no directory
                 * writable including tye temporary
                 * one, we must throw an exception
                 */
                if (!@file_exists($full_path) || !@is_writable($full_path)) {
                    throw new phpFastCacheDriverException('PLEASE CREATE OR CHMOD ' . $full_path . ' - 0777 OR ANY WRITABLE PERMISSION!');
                }
                $this->tmp[ $full_path_hash ] = $full_path;
                $this->htaccessGen($full_path, array_key_exists('htaccess', $this->config) ? $this->config[ 'htaccess' ] : false);
            }
        }
        return realpath($full_path);
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
     * @return bool
     */
    public function isExpired()
    {
        trigger_error(__FUNCTION__ . '() is deprecated, use ExtendedCacheItemInterface::isExpired() instead.', E_USER_DEPRECATED);

        return true;
    }


    /**
     * @return string
     * @throws \phpFastCache\Exceptions\phpFastCacheCoreException
     */
    public function getFileDir()
    {
        return $this->getPath() . DIRECTORY_SEPARATOR . self::FILE_DIR;
    }

    /**
     * @param $keyword
     * @param bool $skip
     * @return string
     * @throws phpFastCacheDriverException
     */
    private function getFilePath($keyword, $skip = false)
    {
        $path = $this->getFileDir();

        if ($keyword === false) {
            return $path;
        }

        $filename = $this->encodeFilename($keyword);
        $folder = substr($filename, 0, 2);
        $path = rtrim($path, '/') . '/' . $folder;
        /**
         * Skip Create Sub Folders;
         */
        if ($skip == false) {
            if (!file_exists($path)) {
                if (@!mkdir($path, $this->setChmodAuto(), true)) {
                    throw new phpFastCacheDriverException('PLEASE CHMOD ' . $this->getPath() . ' - ' . $this->setChmodAuto() . ' OR ANY WRITABLE PERMISSION!');
                }
            }
        }

        return $path . '/' . $filename . '.txt';
    }


    /**
     * @param $this ->config
     * @return int
     */
    public function setChmodAuto()
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
     * @throws \Exception
     */
    protected function htaccessGen($path, $create = true)
    {
        if ($create === true) {
            if (!is_writable($path)) {
                try {
                    if(!chmod($path, 0777)){
                        throw new phpFastCacheDriverException('Chmod failed on : ' . $path);
                    }
                } catch (phpFastCacheDriverException $e) {
                    throw new phpFastCacheDriverException('PLEASE CHMOD ' . $path . ' - 0777 OR ANY WRITABLE PERMISSION!', 0, $e);
                }
            }

            if (!file_exists($path . "/.htaccess")) {
                $htaccess = "<IfModule mod_authz_host>\n
Require all denied\n
</IfModule>\n
<IfModule !mod_authz_host>\n
Order Allow,Deny\n
Deny from all\n
</IfModule>\n";

                $file = @fopen($path . '/.htaccess', 'w+');
                if (!$file) {
                    throw new phpFastCacheDriverException('PLEASE CHMOD ' . $path . ' - 0777 OR ANY WRITABLE PERMISSION!');
                }
                fwrite($file, $htaccess);
                fclose($file);
            }
        }
    }
}