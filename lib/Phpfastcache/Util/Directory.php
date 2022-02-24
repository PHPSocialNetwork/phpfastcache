<?php

/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */
declare(strict_types=1);

namespace Phpfastcache\Util;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Directory
{
    /**
     * Get the directory size
     */
    public static function dirSize(string $directory, bool $includeDirAllocSize = false): int
    {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            /**
             * @var SplFileInfo $file
             */
            if ($file->isFile()) {
                $size += filesize($file->getRealPath());
            } elseif ($includeDirAllocSize) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    public static function getFileCount(string $path): int
    {
        $count = 0;
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $object) {
            /**
             * @var SplFileInfo $object
             */
            if ($object->isFile()) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Recursively delete a directory and all of it's contents - e.g.the equivalent of `rm -r` on the command-line.
     * Consistent with `rmdir()` and `unlink()`, an E_WARNING level error will be generated on failure.
     *
     * @param string $source absolute path to directory or file to delete
     * @param bool $removeOnlyChildren set to true will only remove content inside directory
     *
     * @return bool true on success; false on failure
     */
    public static function rrmdir(string $source, bool $removeOnlyChildren = false): bool
    {
        if (empty($source) || false === file_exists($source)) {
            return false;
        }

        if (is_file($source) || is_link($source)) {
            clearstatcache(true, $source);

            return unlink($source);
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileInfo) {
            /**
             * @var SplFileInfo $fileInfo
             */
            $realpath = $fileInfo->getRealPath();
            if ($realpath) {
                if ($fileInfo->isDir()) {
                    if (false === self::rrmdir($fileInfo->getRealPath())) {
                        return false;
                    }
                } elseif (false === unlink($realpath)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        if (false === $removeOnlyChildren) {
            return rmdir($source);
        }

        return true;
    }

    /**
     * Alias of realpath() but work
     * on non-existing files
     */
    public static function getAbsolutePath(string $path): string
    {
        $parts = preg_split('~[/\\\\]+~', $path, 0, \PREG_SPLIT_NO_EMPTY);
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' === $part) {
                continue;
            }
            if ('..' === $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        /**
         * Allows to dereference char
         */
        $file = preg_replace('~^(([a-z0-9\-]+)://)~', '', __FILE__); // remove file protocols such as "phar://" etc.
        $prefix = \DIRECTORY_SEPARATOR === $file[0] ? \DIRECTORY_SEPARATOR : '';

        return $prefix . implode(\DIRECTORY_SEPARATOR, $absolutes);
    }
}
