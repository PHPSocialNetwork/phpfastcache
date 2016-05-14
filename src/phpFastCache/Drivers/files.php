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

namespace phpFastCache\Drivers;

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Exceptions\phpFastCacheDriverException;

/**
 * Class files
 * @package phpFastCache\Drivers
 */
class files extends DriverAbstract
{
    /**
     * Init Cache Path
     * phpFastCache_files constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct($config = array())
    {
        $this->setup($config);
        $this->getPath(); // force create path

        if (!$this->checkdriver() && !isset($config[ 'skipError' ])) {
            throw new phpFastCacheDriverException("Can't use this driver for your website!");
        }
    }

    /**
     * @return bool
     */
    public function checkdriver()
    {
        if (is_writable($this->getPath())) {
            return true;
        }/* else {

        }*/
        return false;
    }

    /**
     * @param $keyword
     * @param bool $skip
     * @return string
     * @throws phpFastCacheDriverException
     */
    private function getFilePath($keyword, $skip = false)
    {
        $path = $this->getPath();

        $filename = $this->encodeFilename($keyword);
        $folder = substr($filename, 0, 2);
        $path = rtrim($path, '/') . '/' . $folder;
        /**
         * Skip Create Sub Folders;
         */
        if ($skip == false) {
            if (!file_exists($path)) {
                if (!mkdir($path, $this->__setChmodAuto(), true)) {
                    throw new phpFastCacheDriverException('PLEASE CHMOD ' . $this->getPath() . ' - 0777 OR ANY WRITABLE PERMISSION!', 92);
                }
            }
        }

        return $path . '/' . $filename . '.txt';
    }

    /**
     * @param $keyword
     * @param string $value
     * @param int $time
     * @param array $option
     * @return bool
     * @throws \Exception
     */
    public function driver_set($keyword, $value = '', $time = 300, $option = array())
    {
        $file_path = $this->getFilePath($keyword);
        $data = $this->encode($value);

        $toWrite = true;
        /*
         * Skip if Existing Caching in Options
         */
        if (isset($option[ 'skipExisting' ]) && $option[ 'skipExisting' ] == true && file_exists($file_path)) {
            $content = $this->readfile($file_path);
            $old = $this->decode($content);
            $toWrite = false;
            if ($this->isExpired($old)) {
                $toWrite = true;
            }
        }

        // Force write
        try {
            if ($toWrite == true) {
                $f = fopen($file_path, 'w+');
                fwrite($f, $data);
                fclose($f);
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * @param $keyword
     * @param array $option
     * @return mixed|null
     * @throws \Exception
     */
    public function driver_get($keyword, $option = array())
    {

        $file_path = $this->getFilePath($keyword);
        if (!file_exists($file_path)) {
            return null;
        }

        $content = $this->readfile($file_path);
        $object = $this->decode($content);
        if ($this->isExpired($object)) {
            @unlink($file_path);
            $this->autoCleanExpired();
            return null;
        }

        return $object;
    }

    /**
     * @param $keyword
     * @param array $option
     * @return bool
     * @throws \Exception
     */
    public function driver_delete($keyword, $option = array())
    {
        $file_path = $this->getFilePath($keyword, true);
        if (file_exists($file_path) && @unlink($file_path)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return total cache size + auto removed expired files
     * @param array $option
     * @return array
     * @throws \Exception
     */
    public function driver_stats($option = array())
    {
        $res = array(
          'info' => '',
          'size' => '',
          'data' => '',
        );

        $path = $this->getPath();
        $dir = @opendir($path);
        if (!$dir) {
            throw new phpFastCacheDriverException("Can't read PATH:" . $path, 94);
        }

        $total = 0;
        $removed = 0;
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && is_dir($path . '/' . $file)) {
                // read sub dir
                $subdir = opendir($path . "/" . $file);
                if (!$subdir) {
                    throw new phpFastCacheDriverException("Can't read path:" . $path . '/' . $file, 93);
                }

                while ($f = readdir($subdir)) {
                    if ($f != '.' && $f != '..') {
                        $file_path = $path . '/' . $file . '/' . $f;
                        $size = filesize($file_path);
                        $object = $this->decode($this->readfile($file_path));

                        if (strpos($f, '.') === false) {
                            $key = $f;
                        } else {
                            //Because PHP 5.3, this cannot be written in single line
                            $key = explode('.', $f);
                            $key = $key[ 0 ];
                        }
                        $content[ $key ] = array(
                          'size' => $size,
                          'write_time' => (isset($object[ 'write_time' ]) ? $object[ 'write_time' ] : null),
                        );
                        if ($this->isExpired($object)) {
                            @unlink($file_path);
                            $removed += $size;
                        }
                        $total += $size;
                    }
                }
            }
        }

        $res[ 'size' ] = $total - $removed;
        $res[ 'info' ] = array(
          'Total [bytes]' => $total,
          'Expired and removed [bytes]' => $removed,
          'Current [bytes]' => $res[ 'size' ],
        );
        $res[ "data" ] = $content;
        return $res;
    }


    /**
     * @param int $time
     */
    public function autoCleanExpired($time = 3600)
    {
        $autoclean = $this->get('keyword_clean_up_driver_files');
        if ($autoclean == null) {
            $this->set('keyword_clean_up_driver_files', $time);
            $res = $this->stats();
        }
    }

    /**
     * @param array $option
     * @throws \Exception
     * @return void
     */
    public function driver_clean($option = array())
    {

        $path = $this->getPath();
        $dir = @opendir($path);
        if (!$dir) {
            throw new phpFastCacheDriverException("Can't read PATH:" . $path, 94);
        }

        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && is_dir($path . '/' . $file)) {
                // read sub dir
                $subdir = @opendir($path . '/' . $file);
                if (!$subdir) {
                    throw new phpFastCacheDriverException("Can't read path:" . $path . '/' . $file, 93);
                }

                while ($f = readdir($subdir)) {
                    if ($f != '.' && $f != '..') {
                        $file_path = $path . '/' . $file . '/' . $f;
                        @unlink($file_path);
                    }
                }
            }
        }
    }

    /**
     * @param $keyword
     * @return bool
     * @throws \Exception
     */
    public function driver_isExisting($keyword)
    {
        $file_path = $this->getFilePath($keyword, true);
        if (!file_exists($file_path)) {
            return false;
        } else {
            // check expired or not
            $value = $this->get($keyword);

            return !($value == null);
        }
    }

    /**
     * @param $object
     * @return bool
     */
    public function isExpired($object)
    {
        if (isset($object[ 'expired_time' ]) && time() >= $object[ 'expired_time' ]) {
            return true;
        } else {
            return false;
        }
    }
}
