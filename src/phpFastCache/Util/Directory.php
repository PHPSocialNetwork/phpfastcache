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

namespace phpFastCache\Util;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Class Directory
 * @package phpFastCache\Util
 */
class Directory
{
    /**
     * Get the directory size
     * @param string $directory
     * @param bool $includeDirAllocSize
     * @return integer
     */
    public static function dirSize($directory, $includeDirAllocSize = false)
    {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            /**
             * @var \SplFileInfo $file
             */
            if ($file->isFile()) {
                $size += filesize($file->getRealPath());
            } else if ($includeDirAllocSize) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * @param string $path
     * @return int
     */
    public static function getFileCount($path)
    {
        $count = 0;
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $object) {
            /**
             * @var \SplFileInfo $object
             */
            if ($object->isFile()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Recursively delete a directory and all of it's contents - e.g.the equivalent of `rm -r` on the command-line.
     * Consistent with `rmdir()` and `unlink()`, an E_WARNING level error will be generated on failure.
     *
     * @param string $source absolute path to directory or file to delete.
     * @param bool $removeOnlyChildren set to true will only remove content inside directory.
     *
     * @return bool true on success; false on failure
     */
    public static function rrmdir($source, $removeOnlyChildren = false)
    {
        if (empty($source) || file_exists($source) === false) {
            return false;
        }

        if (is_file($source) || is_link($source)) {
            return unlink($source);
        }

        $files = new RecursiveIteratorIterator
        (
          new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
          RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            /**
             * @var SplFileInfo $fileinfo
             */
            if ($fileinfo->isDir()) {
                if (self::rrmdir($fileinfo->getRealPath()) === false) {
                    return false;
                }
            } else if (unlink($fileinfo->getRealPath()) === false) {
                return false;
            }
        }

        if ($removeOnlyChildren === false) {
            return rmdir($source);
        }

        return true;
    }

    /**
     * Alias of realpath() but work
     * on non-existing files
     *
     * @param $path
     * @return string
     */
    public static function getAbsolutePath($path)
    {
        $parts = preg_split('~[/\\\\]+~', $path, 0, PREG_SPLIT_NO_EMPTY);
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
        $__FILE__ = preg_replace('~^(([a-z0-9\-]+)://)~', '', __FILE__);// remove file protocols such as "phar://" etc.
        $prefix = $__FILE__[ 0 ] === DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '';
        return $prefix . implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}