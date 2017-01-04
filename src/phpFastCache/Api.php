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

namespace phpFastCache;

/**
 * Class Api
 * @package phpFastCache
 */
class Api
{
    protected static $version = '1.1.3';

    /**
     * This method will returns the current
     * API version, the API version will be
     * updated by following the semantic versioning
     * based on changes of:
     * - ExtendedCacheItemPoolInterface
     * - ExtendedCacheItemInterface
     *
     * @see  http://semver.org/
     * @return string
     */
    public static function getVersion()
    {
        return self::$version;
    }

    /**
     * Return the API changelog, as a string.
     * @return string
     */
    public static function getChangelog()
    {
        return <<<CHANGELOG
- 1.1.3
-- Added an additional CacheItemInterface method:
   ExtendedCacheItemInterface::getEncodedKey()

- 1.1.2
-- Implemented [de|a]ttaching methods to improve memory management
   ExtendedCacheItemPoolInterface::detachItem()
   ExtendedCacheItemPoolInterface::detachAllItems()
   ExtendedCacheItemPoolInterface::attachItem()
   ExtendedCacheItemPoolInterface::isAttached()

- 1.1.1
-- Implemented JsonSerializable interface to ExtendedCacheItemInterface

- 1.1.0
-- Implemented JSON methods such as:
   ExtendedCacheItemPoolInterface::getItemsAsJsonString()
   ExtendedCacheItemPoolInterface::getItemsByTagsAsJsonString()
   ExtendedCacheItemInterface::getDataAsJsonString()

- 1.0.0
-- First initial version
CHANGELOG;
    }
}