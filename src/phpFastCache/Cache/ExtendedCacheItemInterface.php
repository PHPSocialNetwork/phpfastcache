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

namespace phpFastCache\Cache;

use Psr\Cache\CacheItemInterface;

/**
 * Interface ExtendedCacheItemInterface
 * @package phpFastCache\Cache
 */
interface ExtendedCacheItemInterface extends CacheItemInterface
{
    /**
     * @return bool
     */
    public function getUncommittedData();

    /**
     * @return \DateTimeInterface
     */
    public function getExpirationDate();

    /**
     * @return int
     */
    public function getTtl();

    /**
     * @return bool
     */
    public function isExpired();

    /**
     * @param \phpFastCache\Cache\ExtendedCacheItemPoolInterface $driver
     * @return mixed
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver);

    /**
     * @return $this
     */
    public function increment();

    /**
     * @return $this
     */
    public function decrement();

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
}