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

namespace Phpfastcache\Core\Item;

use Phpfastcache\Core\Pool\TaggableCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

interface TaggableCacheItemInterface
{
    /**
     * Allows you to get cache item(s) by at least **ONE** of the specified matching tag(s).
     * Default behavior
     */
    public const TAG_STRATEGY_ONE = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE;

    /**
     * Allows you to get cache item(s) by **ALL** of the specified matching tag(s)
     * The cache item *CAN* have additional tag(s)
     */
    public const TAG_STRATEGY_ALL = TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL;

    /**
     * Allows you to get cache item(s) by **ONLY** the specified matching tag(s)
     * The cache item *CANNOT* have additional tag(s)
     */
    public const TAG_STRATEGY_ONLY = TaggableCacheItemPoolInterface::TAG_STRATEGY_ONLY;

    /**
     * @param string $tagName
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function addTag(string $tagName): ExtendedCacheItemInterface;

    /**
     * @param string[] $tagNames
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function addTags(array $tagNames): ExtendedCacheItemInterface;

    /**
     * @param string $tagName
     *
     * @return bool
     */
    public function hasTag(string $tagName): bool;

    /**
     * @param string[] $tagNames
     * @param int $strategy
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function hasTags(array $tagNames, int $strategy = self::TAG_STRATEGY_ONE): bool;

    /**
     * @param string[] $tags
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setTags(array $tags): ExtendedCacheItemInterface;

    /**
     * @return string[]
     */
    public function getTags(): array;

    /**
     * @param string $separator
     *
     * @return string
     */
    public function getTagsAsString(string $separator = ', '): string;

    /**
     * @param string $tagName
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function removeTag(string $tagName): ExtendedCacheItemInterface;

    /**
     * @param string[] $tagNames
     *
     * @return ExtendedCacheItemInterface
     */
    public function removeTags(array $tagNames): ExtendedCacheItemInterface;

    /**
     * @return string[]
     */
    public function getRemovedTags(): array;
}
