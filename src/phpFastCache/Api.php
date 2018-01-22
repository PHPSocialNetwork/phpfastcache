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
declare(strict_types=1);

namespace phpFastCache;

use phpFastCache\Exceptions\phpFastCacheIOException;
use phpFastCache\Exceptions\phpFastCacheLogicException;

/**
 * Class Api
 * @package phpFastCache
 */
class Api
{
    protected static $version = '1.3.0';

    /**
     * Api constructor.
     */
    final private function __construct()
    {
    }

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
    public static function getVersion(): string
    {
        return self::$version;
    }

    /**
     * @param bool $fallbackOnChangelog
     * @param bool $cacheable
     * @return string
     * @throws \phpFastCache\Exceptions\phpFastCacheLogicException
     * @throws \phpFastCache\Exceptions\phpFastCacheIOException
     */
    public static function getPhpFastCacheVersion($fallbackOnChangelog = true, $cacheable = true): string
    {
        /**
         * Cache the version statically to improve
         * performances on multiple calls
         */
        static $version;

        if($version && $cacheable){
            return $version;
        }

        if(function_exists('shell_exec')){
            $stdout = shell_exec('git describe --abbrev=0 --tags');
            if(is_string($stdout)){
                $version = trim($stdout);
                return $version;
            }
            throw new phpFastCacheLogicException('The git command used to retrieve the PhpFastCache version has failed.');
        }

        if(!$fallbackOnChangelog){
            throw new phpFastCacheLogicException('shell_exec is disabled therefore the PhpFastCache version cannot be retrieved.');
        }

        $changelogFilename = __DIR__ . '/../../CHANGELOG.md';
        if(file_exists($changelogFilename)){
            $versionPrefix = '## ';
            $changelog = explode("\n", self::getPhpFastCacheChangelog());
            foreach ($changelog as $line){
                if(strpos($line, $versionPrefix) === 0){
                    $version = trim(str_replace($versionPrefix, '', $line));
                    return $version;
                }
            }
            throw new phpFastCacheLogicException('Unable to retrieve the PhpFastCache version through the CHANGELOG.md as no valid string were found in it.');
        }
        throw new phpFastCacheLogicException('shell_exec being disabled we attempted to retrieve the PhpFastCache version through the CHANGELOG.md file but it is not readable or has been removed.');
    }

    /**
     * @param bool $cacheable
     * @return string
     */
    public static function getPhpFastCacheGitHeadHash($cacheable = true)
    {
        static $hash;

        if($hash && $cacheable){
            return $hash;
        }

        if(function_exists('shell_exec')){
            $stdout = shell_exec('git rev-parse --short HEAD');
            if(is_string($stdout)){
                $hash = trim($stdout);
                return "#{$hash}";
            }
        }
        return '';
    }


    /**
     * Return the API changelog, as a string.
     * @return string
     */
    public static function getChangelog(): string
    {
        return <<<CHANGELOG
- 1.3.0
-- Implemented full PHP7 type hint support for ExtendedCacheItemPoolInterface and ExtendedCacheItemInterface
-- Added instance ID getter (introduced in V7):
   ExtendedCacheItemPoolInterface::getInstanceId()
-- The method ExtendedCacheItemPoolInterface::getDefaultConfig() will now return a \phpFastCache\Util\ArrayObject

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

    /**
     * Return the PhpFastCache changelog, as a string.
     * @return string
     * @throws phpFastCacheLogicException
     * @throws phpFastCacheIOException
     */
    public static function getPhpFastCacheChangelog(): string
    {
        $changelogFilename = __DIR__ . '/../../CHANGELOG.md';
        if(file_exists($changelogFilename)){
            $string = str_replace(["\r\n", "\r"], "\n", trim(file_get_contents($changelogFilename)));
            if($string){
                return $string;
            }
            throw new phpFastCacheLogicException('Unable to retrieve the PhpFastCache changelog as it seems to be empty.');
        }
        throw new phpFastCacheIOException('The CHANGELOG.md file is not readable or has been removed.');
    }
}