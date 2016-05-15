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

namespace phpFastCache\Drivers\Files;

use phpFastCache\Cache\ExtendedCacheItemInterface;
use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\PathSeekerTrait;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    use PathSeekerTrait, StandardPsr6StructureTrait;

    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct(array $config = [])
    {
        $this->setup($config);

        if (!$this->driverCheck()) {
            throw new phpFastCacheDriverCheckException(sprintf(self::DRIVER_CHECK_FAILURE, $this->getDriverName()));
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        return is_writable($this->getPath());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $file_path = $this->getFilePath($item->getKey());
            $data = $this->encode($this->driverPreWrap($item));

            $toWrite = true;
            /*
             * Skip if Existing Caching in Options
             */
            if (isset($option[ 'skipExisting' ]) && $option[ 'skipExisting' ] == true && file_exists($file_path)) {
                $content = $this->readfile($file_path);
                $old = $this->decode($content);
                $toWrite = false;
                if ($old->isExpired()) {
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
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function driverRead($key)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        $file_path = $this->getFilePath($key);
        if (!file_exists($file_path)) {
            return null;
        }

        $content = $this->readfile($file_path);
        $object = $this->decode($content);

        if ($this->driverUnwrapTime($object)->getTimestamp() < time()) {
            @unlink($file_path);

            return null;
        }

        return $object;

    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function driverDelete(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $file_path = $this->getFilePath($item->getKey(), true);
            if (file_exists($file_path) && @unlink($file_path)) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    public function driverClear()
    {
        $return = null;
        $path = $this->getPath();
        $dir = @opendir($path);
        if (!$dir) {
            throw new phpFastCacheDriverException("Can't read PATH:" . $path);
        }

        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && is_dir($path . '/' . $file)) {
                // read sub dir
                $subdir = @opendir($path . '/' . $file);
                if (!$subdir) {
                    throw new phpFastCacheDriverException("Can't read path:" . $path . '/' . $file);
                }

                while ($subdirFile = readdir($subdir)) {
                    if ($subdirFile != '.' && $subdirFile != '..') {
                        $file_path = $path . '/' . $file . '/' . $subdirFile;
                        $result = @unlink($file_path);
                        if ($return !== false) {
                            $return = $result;
                        }
                    }
                }
            }
        }

        return (bool) $return;
    }

    /**
     * @return bool
     */
    public function driverConnect()
    {
        return true;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function driverIsHit(CacheItemInterface $item)
    {
        $file_path = $this->getFilePath($item->getKey(), true);
        if (!file_exists($file_path)) {
            return false;
        } else {
            // check expired or not
            $value = $this->driverRead($item->getKey());

            return !($value == null);
        }
    }

    /**
     * @param string $optionName
     * @param mixed $optionValue
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function isValidOption($optionName, $optionValue)
    {
        parent::isValidOption($optionName, $optionValue);
        switch($optionName)
        {
            case 'path':
                return is_string($optionValue);
                break;

            case 'default_chmod':
                return is_numeric($optionValue);
                break;

            case 'securityKey':
                return is_string($optionValue);
                break;
            case 'htaccess':
                return is_bool($optionValue);
                break;
            default:
                return false;
            break;
        }
    }
    /**
     * @return array
     */
    public static function getValidOptions()
    {
        return ['path', 'default_chmod', 'securityKey', 'htaccess'];
    }

    /**
     * @return array
     */
    public static function getRequiredOptions()
    {
        return ['path'];
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return driverStatistic
     * @throws \phpFastCache\Exceptions\phpFastCacheCoreException
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverException
     */
    public function getStats()
    {
        $stat = new driverStatistic();

        $path = $this->getPath();
        $dir = @opendir($path);
        if (!$dir) {
            throw new phpFastCacheDriverException("Can't read PATH:" . $path, 94);
        }

        $total = 0;
        $removed = 0;
        $content = [];
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && is_dir($path . '/' . $file)) {
                // read sub dir
                $subdir = opendir($path . "/" . $file);
                if (!$subdir) {
                    throw new phpFastCacheDriverException("Can't read path:" . $path . '/' . $file);
                }

                while ($subdirFile = readdir($subdir)) {
                    if ($subdirFile != '.' && $subdirFile != '..') {
                        $file_path = $path . '/' . $file . '/' . $subdirFile;
                        $size = filesize($file_path);
                        $object = $this->decode($this->readfile($file_path));

                        if (strpos($subdirFile, '.') === false) {
                            $key = $subdirFile;
                        } else {
                            $key = explode('.', $subdirFile)[ 0 ];
                        }
                        $content[ $key ] = [
                          'size' => $size,
                          'write_time' => (isset($object[ 'write_time' ]) ? $object[ 'write_time' ] : null),
                        ];
                        if ($object->isExpired()) {
                            @unlink($file_path);
                            $removed += $size;
                        }
                        $total += $size;
                    }
                }
            }
        }

        $stat->setData($content)
          ->setSize($total - $removed)
          ->setInfo('Total [bytes]: ' . $total . ', '
            . 'Expired and removed [bytes]: ' . $removed . ', '
            . 'Current [bytes], ' . $total - $removed
          );

        return $stat;
    }
}