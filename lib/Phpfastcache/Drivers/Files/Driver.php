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

namespace Phpfastcache\Drivers\Files;

use Exception;
use FilesystemIterator;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\{ExtendedCacheItemPoolInterface, IO\IOHelperTrait, TaggableCacheItemPoolTrait};
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Exceptions\{PhpfastcacheInvalidArgumentException, PhpfastcacheIOException, PhpfastcacheLogicException};
use Phpfastcache\Util\Directory;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 *
 * Important NOTE:
 * We are using getKey instead of getEncodedKey since this backend create filename that are
 * managed by defaultFileNameHashFunction and not defaultKeyHashFunction
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
{
    use IOHelperTrait;
    use TaggableCacheItemPoolTrait;

    /**
     * @return bool
     * @throws PhpfastcacheIOException
     */
    public function driverCheck(): bool
    {
        return is_writable($this->getPath()) || mkdir($concurrentDirectory = $this->getPath(), $this->getDefaultChmod(), true) || is_dir($concurrentDirectory);
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return null|array
     * @throws PhpfastcacheIOException
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $file_path = $this->getFilePath($item->getKey(), true);

        try{
            $content = $this->readFile($file_path);
        }catch (PhpfastcacheIOException){
            return null;
        }

        return $this->decode($content);
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        $file_path = $this->getFilePath($item->getKey());
        $data = $this->encode($this->driverPreWrap($item));

        /**
         * Force write
         */
        try {
            return $this->writefile($file_path, $data, $this->getConfig()->isSecureFileManipulation());
        } catch (Exception) {
            return false;
        }
}

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        $file_path = $this->getFilePath($item->getKey(), true);
        if (\file_exists($file_path) && @\unlink($file_path)) {
            \clearstatcache(true, $file_path);
            $dir = \dirname($file_path);
            if (!(new FilesystemIterator($dir))->valid()) {
                \rmdir($dir);
            }
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Phpfastcache\Exceptions\PhpfastcacheIOException
     */
    protected function driverClear(): bool
    {
        return Directory::rrmdir($this->getPath(true));
    }
}
