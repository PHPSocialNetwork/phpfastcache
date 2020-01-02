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

namespace Phpfastcache\Core\Item;

use Phpfastcache\Exceptions\{PhpfastcacheInvalidArgumentException};


/**
 * Interface TaggableCacheItemInterface
 * @package Phpfastcache\Core\Item
 */
interface TaggableCacheItemInterface
{
    /**
     * @param string $tagName
     *
     * @return self
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function addTag(string $tagName): self;

    /**
     * @param array $tagNames
     *
     * @return self
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function addTags(array $tagNames): self;


    /**
     * @param array $tags
     *
     * @return self
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setTags(array $tags): self;

    /**
     * @return array
     */
    public function getTags(): array;

    /**
     * @param string $separator
     *
     * @return string
     */
    public function getTagsAsString(string $separator = ', '): string;

    /**
     * @param array $tagName
     *
     * @return self
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function removeTag(string $tagName): self;

    /**
     * @param array $tagNames
     *
     * @return self
     */
    public function removeTags(array $tagNames): self;

    /**
     * @return array
     */
    public function getRemovedTags(): array;
}
