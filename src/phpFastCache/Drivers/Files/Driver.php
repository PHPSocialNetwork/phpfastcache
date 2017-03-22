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

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\PathSeekerTrait;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Util\Directory;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    use PathSeekerTrait;

    /**
     *
     */
    const FILE_DIR = 'files';

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
        return is_writable($this->getFileDir()) || @mkdir($this->getFileDir(), $this->setChmodAuto(), true);
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $file_path = $this->getFilePath($item->getKey());
            $data = $this->encode($this->driverPreWrap($item));

            $toWrite = true;

            /**
             * Skip if Existing Caching in Options
             */
            if (isset($this->config[ 'skipExisting' ]) && $this->config[ 'skipExisting' ] == true && file_exists($file_path)) {
                $content = $this->readfile($file_path);
                $old = $this->decode($content);
                $toWrite = false;
                if ($old->isExpired()) {
                    $toWrite = true;
                }
            }

            /**
             * Force write
             */
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
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    protected function driverRead(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        $file_path = $this->getFilePath($item->getKey());
        if (!file_exists($file_path)) {
            return null;
        }

        $content = $this->readfile($file_path);

        return $this->decode($content);

    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $file_path = $this->getFilePath($item->getKey(), true);
            if (file_exists($file_path) && @unlink($file_path)) {
                $dir = dirname($file_path);
                if (!(new \FilesystemIterator($dir))->valid()) {
                    rmdir($dir);
                }
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
    protected function driverClear()
    {
        return (bool) Directory::rrmdir($this->getPath(true));
    }

    /**
     * @return bool
     */
    protected function driverConnect()
    {
        return true;
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
        switch ($optionName) {
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
        $path = $this->getFilePath(false);

        if (!is_dir($path)) {
            throw new phpFastCacheDriverException("Can't read PATH:" . $path, 94);
        }

        $stat->setData(implode(', ', array_keys($this->itemInstances)))
          ->setRawData([])
          ->setSize(Directory::dirSize($path))
          ->setInfo('Number of files used to build the cache: ' . Directory::getFileCount($path));

        return $stat;
    }
}
