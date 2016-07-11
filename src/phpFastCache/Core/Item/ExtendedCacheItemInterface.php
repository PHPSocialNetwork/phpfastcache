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

use Psr\Cache\CacheItemInterface;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;

/**
 * Interface ExtendedCacheItemInterface
 * @package phpFastCache\Cache
 */
interface ExtendedCacheItemInterface extends CacheItemInterface, \JsonSerializable
{
    /**
     * @return mixed
     */
    public function getUncommittedData();

    /**
     * @return \DateTimeInterface|null
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
     * @throws \LogicException
     */
    public function getCreationDate();
    
    /**
     * @return \DateTimeInterface
     * @throws \LogicException
     */
    public function getModificationDate();

    /**
     * @param $date \DateTimeInterface
     * @return $this
     * @throws \LogicException
     */
    public function setCreationDate(\DateTimeInterface $date);

    /**
     * @param $date \DateTimeInterface
     * @return $this
     * @throws \LogicException
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
     * @param \phpFastCache\Cache\Pool\ExtendedCacheItemPoolInterface $driver
     * @return mixed
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver);

    /**
     * @param bool $isHit
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setHit($isHit);

    /**
     * @param int $step
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function increment($step = 1);

    /**
     * @param int $step
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function decrement($step = 1);

    /**
     * @param array|string $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function append($data);

    /**
     * @param array|string $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function prepend($data);


    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|\DateInterval $time
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration. If null is passed explicitly, a default value MAY be used.
     *   If none is set, the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     *
     * @deprecated Use CacheItemInterface::expiresAfter() instead
     */
    public function touch($time);

    /**
     * @param string $tagName
     * @return $this
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
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
}