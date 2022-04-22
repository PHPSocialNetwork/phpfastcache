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

namespace Phpfastcache;

use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Helper\UninstanciableObjectTrait;

/**
 * Class Api
 * @package Phpfastcache
 */
class Api
{
    use UninstanciableObjectTrait;

    protected static string $version = '4.2.0';

    /**
     * This method will return the current
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
     * @see https://semver.org/
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
    public static function getPhpfastcacheVersion(bool $fallbackOnChangelog = true, bool $cacheable = true): string
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
                return trim($stdout);
            }
            if (!$fallbackOnChangelog) {
                throw new PhpfastcacheLogicException('The git command used to retrieve the Phpfastcache version has failed.');
            }
        }

        if (!$fallbackOnChangelog) {
            throw new PhpfastcacheLogicException('shell_exec is disabled therefore the Phpfastcache version cannot be retrieved.');
        }

        $changelogFilename = __DIR__ . '/../../CHANGELOG.md';
        if (\file_exists($changelogFilename)) {
            $semverRegexp = '/^([\d]+)\.([\d]+)\.([\d]+)(?:-([\dA-Za-z-]+(?:\.[\dA-Za-z-]+)*))?(?:\+[\dA-Za-z-]+)?$/';
            $changelog = \explode("\n", self::getPhpfastcacheChangelog());
            foreach ($changelog as $line) {
                $trimmedLine = \trim($line, " \t\n\r\0\x0B#");
                if (\str_starts_with($line, '#') && \preg_match($semverRegexp, $trimmedLine)) {
                    return $trimmedLine;
                }
            }
            throw new PhpfastcacheLogicException('Unable to retrieve the Phpfastcache version through the CHANGELOG.md as no valid string were found in it.');
        }
        throw new PhpfastcacheLogicException(
            'shell_exec being disabled we attempted to retrieve the Phpfastcache version through the CHANGELOG.md file but it is not readable or has been removed.'
        );
    }

    /**
     * Return the Phpfastcache changelog, as a string.
     * @return string
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheIOException
     */
    public static function getPhpfastcacheChangelog(): string
    {
        $changelogFilename = __DIR__ . '/../../CHANGELOG.md';
        if (\file_exists($changelogFilename)) {
            $string = \str_replace(["\r\n", "\r"], "\n", \trim(\file_get_contents($changelogFilename)));
            if ($string) {
                return $string;
            }
            throw new PhpfastcacheLogicException('Unable to retrieve the Phpfastcache changelog as it seems to be empty.');
        }
        throw new PhpfastcacheIOException('The CHANGELOG.md file is not readable or has been removed.');
    }

    /**
     * @param bool $cacheable
     * @return string
     */
    public static function getPhpfastcacheGitHeadHash(bool $cacheable = true): string
    {
        static $hash;

        if ($hash && $cacheable) {
            return $hash;
        }

        if (\function_exists('shell_exec')) {
            $stdout = \shell_exec('git rev-parse --short HEAD');
            if (\is_string($stdout)) {
                return '#' . \trim($stdout);
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
            throw new PhpfastcacheLogicException('Unable to retrieve the Phpfastcache API changelog as it seems to be empty.');
        }
        throw new PhpfastcacheIOException('The CHANGELOG_API.md file is not readable or has been removed.');
    }
}
