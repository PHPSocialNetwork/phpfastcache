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

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

trait TaggableCacheItemTrait
{
    use ExtendedCacheItemTrait;

    /**
     * @var string[]
     */
    protected array $tags = [];

    /**
     * @var string[]
     */
    protected array $removedTags = [];

    /**
     * @param string[] $tagNames
     * @return ExtendedCacheItemInterface
     */
    public function addTags(array $tagNames): ExtendedCacheItemInterface
    {
        foreach ($tagNames as $tagName) {
            $this->addTag($tagName);
        }

        return $this;
    }

    /**
     * @param string $tagName
     * @return ExtendedCacheItemInterface
     */
    public function addTag(string $tagName): ExtendedCacheItemInterface
    {
        $this->tags = \array_unique(\array_merge($this->tags, [$tagName]));

        return $this;
    }

    /**
     * @param string $tagName
     *
     * @return bool
     */
    public function hasTag(string $tagName): bool
    {
        return \in_array($tagName, $this->tags, true);
    }

    /**
     * @param string[] $tagNames
     * @param int $strategy
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function hasTags(array $tagNames, int $strategy = TaggableCacheItemInterface::TAG_STRATEGY_ONE): bool
    {
        return match ($strategy) {
            TaggableCacheItemInterface::TAG_STRATEGY_ONE => !empty(array_intersect($tagNames, $this->tags)),
            TaggableCacheItemInterface::TAG_STRATEGY_ALL => empty(\array_diff($tagNames, $this->tags)),
            TaggableCacheItemInterface::TAG_STRATEGY_ONLY => empty(\array_diff($tagNames, $this->tags)) && empty(\array_diff($this->tags, $tagNames)),
            default => throw new PhpfastcacheInvalidArgumentException('Invalid strategy constant provided.'),
        };
    }


    /**
     * @param string[] $tags
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setTags(array $tags): ExtendedCacheItemInterface
    {
        if ($tags === [] || \array_filter($tags, 'is_string')) {
            $this->tags = $tags;
        } else {
            throw new PhpfastcacheInvalidArgumentException('$tagName must be an array of string');
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param string $separator
     * @return string
     */
    public function getTagsAsString(string $separator = ', '): string
    {
        return \implode($separator, $this->tags);
    }

    /**
     * @param string[] $tagNames
     * @return ExtendedCacheItemInterface
     */
    public function removeTags(array $tagNames): ExtendedCacheItemInterface
    {
        foreach ($tagNames as $tagName) {
            $this->removeTag($tagName);
        }

        return $this;
    }

    /**
     * @param string $tagName
     * @return ExtendedCacheItemInterface
     */
    public function removeTag(string $tagName): ExtendedCacheItemInterface
    {
        if (($key = \array_search($tagName, $this->tags, true)) !== false) {
            unset($this->tags[$key]);
            $this->removedTags[] = $tagName;
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRemovedTags(): array
    {
        return \array_diff($this->removedTags, $this->tags);
    }

    /**
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function cloneInto(ExtendedCacheItemInterface $itemTarget, ?ExtendedCacheItemPoolInterface $itemPoolTarget = null): void
    {
        $itemTarget->setEventManager($this->getEventManager())
            ->set($this->getRawValue())
            ->setHit($this->isHit())
            ->setTags($this->getTags())
            ->expiresAt(clone $this->getExpirationDate())
            ->setDriver($itemPoolTarget ?? $this->driver);

        if ($this->driver->getConfig()->isItemDetailedDate()) {
            $itemTarget->setCreationDate(clone $this->getCreationDate())
                ->setModificationDate(clone $this->getModificationDate());
        }
    }
}
