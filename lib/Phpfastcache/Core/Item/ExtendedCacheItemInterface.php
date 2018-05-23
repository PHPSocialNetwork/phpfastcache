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

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventInterface;
use Phpfastcache\Exceptions\{
    PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException
};
use Psr\Cache\CacheItemInterface;

/**
 * Interface ExtendedCacheItemInterface
 * @package phpFastCache\Cache
 */
interface ExtendedCacheItemInterface extends CacheItemInterface, \JsonSerializable
{
    /**
     * Returns the encoded key for the current cache item.
     * Usually as a MD5 hash
     *
     * @return string
     *   The encoded key string for this cache item.
     */
    public function getEncodedKey(): string;

    /**
     * @return \DateTimeInterface
     */
    public function getExpirationDate(): \DateTimeInterface;

    /**
     * Alias of expireAt() with forced $expiration param
     *
     * @param \DateTimeInterface $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return self
     *   The called object.
     */
    public function setExpirationDate(\DateTimeInterface $expiration): self;

    /**
     * @return \DateTimeInterface
     * @throws PhpfastcacheLogicException
     */
    public function getCreationDate(): \DateTimeInterface;

    /**
     * @return \DateTimeInterface
     * @throws PhpfastcacheLogicException
     */
    public function getModificationDate(): \DateTimeInterface;

    /**
     * @param $date \DateTimeInterface
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setCreationDate(\DateTimeInterface $date): self;

    /**
     * @param $date \DateTimeInterface
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setModificationDate(\DateTimeInterface $date): self;

    /**
     * @return int
     */
    public function getTtl(): int;

    /**
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * @return bool
     */
    public function isNull(): bool;

    /**
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Return the data length:
     * - Either the number of char if it's a string (binary mode)
     * - or the number of element if it's an array
     * - or the number returned by count() if it's an object implementing \Countable interface
     * - or -1 for anything else
     * @return int
     */
    public function getLength(): int;

    /**
     * @param \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface $driver
     * @return mixed
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver);

    /**
     * @param bool $isHit
     * @return self
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setHit($isHit): self;

    /**
     * @param int $step
     * @return self
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function increment($step = 1): self;

    /**
     * @param int $step
     * @return self
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function decrement($step = 1): self;

    /**
     * @param array|string $data
     * @return self
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function append($data): self;

    /**
     * @param array|string $data
     * @return self
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function prepend($data): self;

    /**
     * @param string $tagName
     * @return self
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function addTag($tagName): self;

    /**
     * @param array $tagNames
     * @return self
     */
    public function addTags(array $tagNames): self;


    /**
     * @param array $tags
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
     * @return string
     */
    public function getTagsAsString($separator = ', '): string;

    /**
     * @param array $tagName
     * @return self
     */
    public function removeTag($tagName): self;

    /**
     * @param array $tagNames
     * @return self
     */
    public function removeTags(array $tagNames): self;

    /**
     * @return array
     */
    public function getRemovedTags(): array;

    /**
     * Return the data as a well-formatted string.
     * Any scalar value will be casted to an array
     * @param int $option \json_encode() options
     * @param int $depth \json_encode() depth
     * @return string
     */
    public function getDataAsJsonString($option = 0, $depth = 512): string;

    /**
     * Set the EventManager instance
     *
     * @param EventInterface $em
     * @return self
     */
    public function setEventManager(EventInterface $em): self;
}