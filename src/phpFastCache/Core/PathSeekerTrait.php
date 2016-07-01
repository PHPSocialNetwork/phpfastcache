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

trait PathSeekerTrait
{
    /**
     * @var array
     */
    public $tmp = [];

    /**
     * @param bool $skip_create_path
     * @param $config
     * @return string
     * @throws \Exception
     */
    public function getPath($getBasePath = false)
    {
        $tmp_dir = rtrim(ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'phpfastcache';

        if (!isset($this->config[ 'path' ]) || $this->config[ 'path' ] == '') {
            if (self::isPHPModule()) {
                $path = $tmp_dir;
            } else {
                $document_root_path = rtrim($_SERVER[ 'DOCUMENT_ROOT' ], '/') . '/../';
                $path = isset($_SERVER[ 'DOCUMENT_ROOT' ]) && is_writable($document_root_path) ? $document_root_path : rtrim(__DIR__, '/') . '/';
            }

            if ($this->config[ 'path' ] != '') {
                $path = $this->config[ 'path' ];
            }

        } else {
            $path = $this->config[ 'path' ];
        }

        if ($getBasePath === true) {
            return $path;
        }

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

        $full_path = rtrim($path, '/') . '/' . $securityKey;
        $full_pathx = md5($full_path);


        if (!isset($this->tmp[ $full_pathx ])) {

            if (!@file_exists($full_path) || !@is_writable($full_path)) {
                if (!@file_exists($full_path)) {
                    @mkdir($full_path, $this->setChmodAuto(), true);
                }
                if (!@is_writable($full_path)) {
                    @chmod($full_path, $this->setChmodAuto());
                }
                if (!@is_writable($full_path)) {
                    // switch back to tmp dir again if the path is not writeable
                    $full_path = rtrim($tmp_dir, '/') . '/' . $securityKey;
                    if (!@file_exists($full_path)) {
                        @mkdir($full_path, $this->setChmodAuto(), true);
                    }
                    if (!@is_writable($full_path)) {
                        @chmod($full_path, $this->setChmodAuto());
                    }
                }
                if (!@file_exists($full_path) || !@is_writable($full_path)) {
                    throw new phpFastCacheCoreException('PLEASE CREATE OR CHMOD ' . $full_path . ' - 0777 OR ANY WRITABLE PERMISSION!', 92);
                }
            }

            $this->tmp[ $full_pathx ] = true;
            $this->htaccessGen($full_path, array_key_exists('htaccess', $this->config) ? $this->config[ 'htaccess' ] : false);
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
                $html = "order deny, allow \r\n
deny from all \r\n
allow from 127.0.0.1";

                $file = @fopen($path . '/.htaccess', 'w+');
                if (!$file) {
                    throw new phpFastCacheDriverException('PLEASE CHMOD ' . $path . ' - 0777 OR ANY WRITABLE PERMISSION!');
                }
                fwrite($file, $html);
                fclose($file);
            }
        }
    }
}