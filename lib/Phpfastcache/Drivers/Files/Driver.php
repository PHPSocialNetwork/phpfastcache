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
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\IO\IOHelperTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Util\Directory;

/**
 * @method Config getConfig()
 *
 * Important NOTE:
 * We are using getKey instead of getEncodedKey since this backend create filename that are
 * managed by defaultFileNameHashFunction and not defaultKeyHashFunction
 */
class Driver implements AggregatablePoolInterface
{
    use IOHelperTrait;

    /**
     * @return bool
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheInvalidArgumentException
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
     * @return ?array<string, mixed>
     * @throws PhpfastcacheIOException
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $filePath = $this->getFilePath($item->getKey(), true);

        try {
            $content = $this->readFile($filePath);
        } catch (PhpfastcacheIOException) {
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

        $filePath = $this->getFilePath($item->getKey());
        $data = $this->encode($this->driverPreWrap($item));

        try {
            return $this->writeFile($filePath, $data, $this->getConfig()->isSecureFileManipulation());
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

        $filePath = $this->getFilePath($item->getKey(), true);
        if (\file_exists($filePath) && @\unlink($filePath)) {
            \clearstatcache(true, $filePath);
            $dir = \dirname($filePath);
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
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverClear(): bool
    {
        return Directory::rrmdir($this->getPath(true));
    }
}
