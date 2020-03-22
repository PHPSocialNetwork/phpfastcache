<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author  Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author  Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Core\Item;

use DateTimeInterface;
use JsonSerializable;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventManagerDispatcherInterface;
use Phpfastcache\Exceptions\{PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException};
use Phpfastcache\Util\ClassNamespaceResolverInterface;
use Psr\Cache\CacheItemInterface;

/**
 * Interface ExtendedCacheItemInterface
 *
 * @package phpFastCache\Cache
 */
interface ExtendedCacheItemInterface extends CacheItemInterface, EventManagerDispatcherInterface, ClassNamespaceResolverInterface, JsonSerializable, TaggableCacheItemInterface
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
     * @return mixed
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver);

    /**
     * @param bool $isHit
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setHit($isHit): ExtendedCacheItemInterface;

    /**
     * @param int $step
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function increment($step = 1): ExtendedCacheItemInterface;

    /**
     * @param int $step
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function decrement($step = 1): ExtendedCacheItemInterface;

    /**
     * @param array|string $data
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function append($data): ExtendedCacheItemInterface;

    /**
     * @param array|string $data
     *
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function prepend($data): ExtendedCacheItemInterface;

    /**
     * Return the data as a well-formatted string.
     * Any scalar value will be casted to an array
     *
     * @param int $option \json_encode() options
     * @param int $depth \json_encode() depth
     *
     * @return string
     */
    public function getDataAsJsonString(int $option = 0, int $depth = 512): string;

    /**
     * @param ExtendedCacheItemPoolInterface $driverPool
     * @return bool
     */
    public function doesItemBelongToThatDriverBackend(ExtendedCacheItemPoolInterface $driverPool): bool;
}
