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

use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Core\Pool\IO\IOHelperTrait;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Util\Directory;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait, IOHelperTrait;

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
        return is_writable($this->getPath()) || @mkdir($this->getPath(), $this->getDefaultChmod(), true);
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $file_path = $this->getFilePath($item->getKey());
            $data = $this->encode($this->driverPreWrap($item));

            /**
             * Force write
             */
            try {
                return $this->writefile($file_path, $data, $this->config[ 'secureFileManipulation' ]);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        $file_path = $this->getFilePath($item->getKey(), true);
        if (!file_exists($file_path)) {
            return null;
        }

        $content = $this->readfile($file_path);

        return $this->decode($content);

    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
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
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return (bool)Directory::rrmdir($this->getPath(true));
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
     * @throws phpFastCacheInvalidArgumentException
     */
    public static function isValidOption($optionName, $optionValue)
    {
        DriverBaseTrait::isValidOption($optionName, $optionValue);
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

            case 'secureFileManipulation':
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
        return ['path', 'default_chmod', 'securityKey', 'htaccess', 'secureFileManipulation'];
    }

    /**
     * @return array
     */
    public static function getRequiredOptions()
    {
        return ['path'];
    }
}
