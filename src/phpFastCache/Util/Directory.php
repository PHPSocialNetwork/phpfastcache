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
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            /**
             * @var \SplFileInfo $file
             */
            if($file->isFile())
            {
                $size += filesize($file->getRealPath());
            }
            else if($includeDirAllocSize)
            {
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
        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
        foreach($objects as $object){
            /**
             * @var \SplFileInfo $object
             */
            if($object->isFile())
            {
                $count++;
            }
        }
        return $count;
    }
}