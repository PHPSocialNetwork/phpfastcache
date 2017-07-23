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
    protected static $version = '1.2.5';

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
- 1.2.5
-- Implemented additional simple helper method to direct access to a config option:
   ExtendedCacheItemPoolInterface::getConfigOption()

- 1.2.4
-- Implemented additional simple helper method to provide basic information about the driver:
   ExtendedCacheItemPoolInterface::getHelp()

- 1.2.3
-- Implemented additional saving method form multiple items:
   ExtendedCacheItemPoolInterface::saveMultiple()

- 1.2.2
-- Implemented additional tags methods such as:
   ExtendedCacheItemPoolInterface::getItemsByTagsAll()
   ExtendedCacheItemPoolInterface::incrementItemsByTagsAll()
   ExtendedCacheItemPoolInterface::decrementItemsByTagsAll()
   ExtendedCacheItemPoolInterface::deleteItemsByTagsAll()
   ExtendedCacheItemPoolInterface::appendItemsByTagsAll()
   ExtendedCacheItemPoolInterface::prependItemsByTagsAll()

- 1.2.1
-- Implemented Event manager methods such as:
   ExtendedCacheItemInterface::setEventManager()
   ExtendedCacheItemPoolInterface::setEventManager()

- 1.2.0
-- Implemented Item advanced time methods such as:
   ExtendedCacheItemInterface::setExpirationDate() (Alias of CacheItemInterface::ExpireAt() for more code logic)
   ExtendedCacheItemInterface::getCreationDate() * 
   ExtendedCacheItemInterface::getModificationDate() *
   ExtendedCacheItemInterface::setCreationDate(\DateTimeInterface) *
   ExtendedCacheItemInterface::setModificationDate() *
   * Require configuration directive "itemDetailedDate" to be enabled

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