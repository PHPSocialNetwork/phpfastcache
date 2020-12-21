<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache;

use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * Class Api
 * @package phpFastCache
 */
class Api
{
    protected static $version = '3.0.0';

    /**
     * Api constructor.
     */
    final protected function __construct()
    {
        // The Api is not meant to be instantiated
    }

    /**
     * This method will returns the current
     * API version, the API version will be
     * updated by following the semantic versioning
     * based on changes of:
     * - ExtendedCacheItemPoolInterface
     * - ExtendedCacheItemInterface
     * - AggregatablePoolInterface
     * - AggregatorInterface
     * - ClusterPoolInterface
     * - EventManagerInterface
     *
     * @see  https://semver.org/
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
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheIOException
     */
    public static function getPhpFastCacheVersion(bool $fallbackOnChangelog = true, bool $cacheable = true): string
    {
        /**
         * Cache the version statically to improve
         * performances on multiple calls
         */
        static $version;

        if ($version && $cacheable) {
            return $version;
        }

        if (\function_exists('shell_exec')) {
            $command = 'git -C "' . __DIR__ . '" describe --abbrev=0 --tags';
            $stdout = \shell_exec($command);
            if (\is_string($stdout)) {
                $version = trim($stdout);
                return $version;
            }
            if (!$fallbackOnChangelog) {
                throw new PhpfastcacheLogicException('The git command used to retrieve the PhpFastCache version has failed.');
            }
        }

        if (!$fallbackOnChangelog) {
            throw new PhpfastcacheLogicException('shell_exec is disabled therefore the PhpFastCache version cannot be retrieved.');
        }

        $changelogFilename = __DIR__ . '/../../CHANGELOG.md';
        if (\file_exists($changelogFilename)) {
            $versionPrefix = '## ';
            $changelog = \explode("\n", self::getPhpFastCacheChangelog());
            foreach ($changelog as $line) {
                if (\strpos($line, $versionPrefix) === 0) {
                    $version = \trim(\str_replace($versionPrefix, '', $line));
                    return $version;
                }
            }
            throw new PhpfastcacheLogicException('Unable to retrieve the PhpFastCache version through the CHANGELOG.md as no valid string were found in it.');
        }
        throw new PhpfastcacheLogicException(
            'shell_exec being disabled we attempted to retrieve the PhpFastCache version through the CHANGELOG.md file but it is not readable or has been removed.'
        );
    }

    /**
     * Return the PhpFastCache changelog, as a string.
     * @return string
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheIOException
     */
    public static function getPhpFastCacheChangelog(): string
    {
        $changelogFilename = __DIR__ . '/../../CHANGELOG.md';
        if (\file_exists($changelogFilename)) {
            $string = \str_replace(["\r\n", "\r"], "\n", \trim(\file_get_contents($changelogFilename)));
            if ($string) {
                return $string;
            }
            throw new PhpfastcacheLogicException('Unable to retrieve the PhpFastCache changelog as it seems to be empty.');
        }
        throw new PhpfastcacheIOException('The CHANGELOG.md file is not readable or has been removed.');
    }

    /**
     * @param bool $cacheable
     * @return string
     */
    public static function getPhpFastCacheGitHeadHash(bool $cacheable = true): string
    {
        static $hash;

        if ($hash && $cacheable) {
            return $hash;
        }

        if (\function_exists('shell_exec')) {
            $stdout = \shell_exec('git rev-parse --short HEAD');
            if (\is_string($stdout)) {
                $hash = \trim($stdout);
                return "#{$hash}";
            }
        }
        return '';
    }

    /**
     * Return the API changelog, as a string.
     * @return string
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheIOException
     */
    public static function getChangelog(): string
    {
        $changelogFilename = __DIR__ . '/../../CHANGELOG_API.md';
        if (\file_exists($changelogFilename)) {
            $string = \str_replace(["\r\n", "\r"], "\n", \trim(\file_get_contents($changelogFilename)));
            if ($string) {
                return $string;
            }
            throw new PhpfastcacheLogicException('Unable to retrieve the PhpFastCache API changelog as it seems to be empty.');
        }
        throw new PhpfastcacheIOException('The CHANGELOG_API.md file is not readable or has been removed.');
    }
}
