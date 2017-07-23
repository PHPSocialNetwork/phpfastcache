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

namespace phpFastCache\Core\Item;

use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\EventManager;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheLogicException;
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
    public function getEncodedKey();

    /**
     * @return mixed
     */
    public function getUncommittedData();

    /**
     * @return \DateTimeInterface
     */
    public function getExpirationDate();

    /**
     * Alias of expireAt() with forced $expiration param
     *
     * @param \DateTimeInterface $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function setExpirationDate(\DateTimeInterface $expiration);

    /**
     * @return \DateTimeInterface
     * @throws phpFastCacheLogicException
     */
    public function getCreationDate();

    /**
     * @return \DateTimeInterface
     * @throws phpFastCacheLogicException
     */
    public function getModificationDate();

    /**
     * @param $date \DateTimeInterface
     * @return $this
     * @throws phpFastCacheLogicException
     */
    public function setCreationDate(\DateTimeInterface $date);

    /**
     * @param $date \DateTimeInterface
     * @return $this
     * @throws phpFastCacheLogicException
     */
    public function setModificationDate(\DateTimeInterface $date);

    /**
     * @return int
     */
    public function getTtl();

    /**
     * @return bool
     */
    public function isExpired();

    /**
     * @param \phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface $driver
     * @return mixed
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver);

    /**
     * @param bool $isHit
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
     */
    public function setHit($isHit);

    /**
     * @param int $step
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
     */
    public function increment($step = 1);

    /**
     * @param int $step
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
     */
    public function decrement($step = 1);

    /**
     * @param array|string $data
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
     */
    public function append($data);

    /**
     * @param array|string $data
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
     */
    public function prepend($data);

    /**
     * @param string $tagName
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
     */
    public function addTag($tagName);

    /**
     * @param array $tagNames
     * @return $this
     */
    public function addTags(array $tagNames);


    /**
     * @param array $tags
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
     */
    public function setTags(array $tags);

    /**
     * @return array
     */
    public function getTags();

    /**
     * @param string $separator
     * @return mixed
     */
    public function getTagsAsString($separator = ', ');

    /**
     * @param array $tagName
     * @return $this
     */
    public function removeTag($tagName);

    /**
     * @param array $tagNames
     * @return $this
     */
    public function removeTags(array $tagNames);

    /**
     * @return array
     */
    public function getRemovedTags();

    /**
     * Return the data as a well-formatted string.
     * Any scalar value will be casted to an array
     * @param int $option json_encode() options
     * @param int $depth json_encode() depth
     * @return string
     */
    public function getDataAsJsonString($option = 0, $depth = 512);

    /**
     * Set the EventManager instance
     *
     * @param EventManager $em
     * @return static
     */
    public function setEventManager(EventManager $em);
}