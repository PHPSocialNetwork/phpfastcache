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

use DateTimeInterface;
use JsonSerializable;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventManagerDispatcherInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Util\ClassNamespaceResolverInterface;
use Psr\Cache\CacheItemInterface;

interface ExtendedCacheItemInterface extends
    CacheItemInterface,
    EventManagerDispatcherInterface,
    ClassNamespaceResolverInterface,
    JsonSerializable,
    TaggableCacheItemInterface
{
    /**
     * Returns the encoded key for the current cache item.
     * Is a MD5 (default),SHA1,SHA256 hash if "defaultKeyHashFunction" config option is configured
     * Else return the plain cache item key "defaultKeyHashFunction" config option is emptied
     *
     * @return string
     *   The encoded key string for this cache item.
     */
    public function getEncodedKey(): string;

    /**
     * Returns the raw value, regardless of hit status.
     * This method can be called if the cache item is NOT YET
     * persisted, and you need to access to its set value.
     *
     * Although not part of the CacheItemInterface, this method is used by
     * the pool for extracting information for saving.
     *
     * @return mixed
     *
     * @internal
     */
    public function getRawValue(): mixed;

    /**
     * @return DateTimeInterface
     */
    public function getExpirationDate(): DateTimeInterface;

    /**
     * Alias of expireAt() with forced $expiration param
     *
     * @param DateTimeInterface $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return ExtendedCacheItemInterface
     *   The called object.
     */
    public function setExpirationDate(DateTimeInterface $expiration): ExtendedCacheItemInterface;

    /**
     * @return DateTimeInterface
     * @throws PhpfastcacheLogicException
     */
    public function getCreationDate(): DateTimeInterface;

    /**
     * @return DateTimeInterface
     * @throws PhpfastcacheLogicException
     */
    public function getModificationDate(): DateTimeInterface;

    /**
     * @param $date DateTimeInterface
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheLogicException
     */
    public function setCreationDate(DateTimeInterface $date): ExtendedCacheItemInterface;

    /**
     * @param $date DateTimeInterface
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheLogicException
     */
    public function setModificationDate(DateTimeInterface $date): ExtendedCacheItemInterface;

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
     *
     * @return int
     */
    public function getLength(): int;

    /**
     * @param ExtendedCacheItemPoolInterface $driver
     *
     * @return ExtendedCacheItemInterface
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver): ExtendedCacheItemInterface;

    /**
     * @param bool $isHit
     *
     * @return ExtendedCacheItemInterface
     */
    public function setHit(bool $isHit): ExtendedCacheItemInterface;

    /**
     * @param int $step
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidTypeException
     */
    public function increment(int $step = 1): ExtendedCacheItemInterface;

    /**
     * @param int $step
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidTypeException
     */
    public function decrement(int $step = 1): ExtendedCacheItemInterface;

    /**
     * @param mixed[]|string $data
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidTypeException
     */
    public function append(array|string $data): ExtendedCacheItemInterface;

    /**
     * @param mixed[]|string $data
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidTypeException
     */
    public function prepend(array|string $data): ExtendedCacheItemInterface;

    /**
     * Return the data as a well-formatted string.
     * Any scalar value will be casted to an array
     *
     * @param int $options \json_encode() options
     * @param int $depth \json_encode() depth
     *
     * @return string
     */
    public function getDataAsJsonString(int $options = JSON_THROW_ON_ERROR, int $depth = 512): string;

    /**
     * @param ExtendedCacheItemPoolInterface $driverPool
     * @return bool
     */
    public function doesItemBelongToThatDriverBackend(ExtendedCacheItemPoolInterface $driverPool): bool;

    /**
     * @param ExtendedCacheItemInterface $itemTarget
     * @param ExtendedCacheItemPoolInterface|null $itemPoolTarget
     */
    public function cloneInto(ExtendedCacheItemInterface $itemTarget, ?ExtendedCacheItemPoolInterface $itemPoolTarget = null): void;
}
