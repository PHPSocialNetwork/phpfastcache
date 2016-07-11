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

use phpFastCache\Proxy\phpFastCacheAbstractProxy;

trait ItemBaseTrait
{
    use ItemExtendedTrait;

    /**
     * @var bool
     */
    protected $fetched = false;

    /**
     * @var phpFastCacheAbstractProxy
     */
    protected $driver;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var \DateTime
     */
    protected $expirationDate;

    /**
     * @var \DateTime
     */
    protected $creationDate;

    /**
     * @var \DateTime
     */
    protected $modificationDate;

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @var array
     */
    protected $removedTags = [];

    /**
     * @var bool
     */
    protected $isHit = false;

    /********************
     *
     * PSR-6 Methods
     *
     *******************/

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->data;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function set($value)
    {
        /**
         * The user set a value,
         * therefore there is no need to
         * fetch from source anymore
         */
        $this->fetched = true;
        $this->data = $value;

        return $this;
    }

    /**
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isHit()
    {
        return $this->isHit;
    }

    /**
     * @param bool $isHit
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setHit($isHit)
    {
        if (is_bool($isHit)) {
            $this->isHit = $isHit;

            return $this;
        } else {
            throw new \InvalidArgumentException('$isHit must be a boolean');
        }
    }

    /**
     * @param \DateTimeInterface $expiration
     * @return $this
     */
    public function expiresAt($expiration)
    {
        if ($expiration instanceof \DateTimeInterface) {
            $this->expirationDate = $expiration;
        } else {
            throw new \InvalidArgumentException('$expiration must be an object implementing the DateTimeInterface');
        }

        return $this;
    }

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
    public function touch($time)
    {
        trigger_error('touch() is deprecated and will be removed in the next major release, use CacheItemInterface::expiresAfter() instead');

        return $this->expiresAfter($time);
    }

    /**
     * @param \DateInterval|int $time
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function expiresAfter($time)
    {
        if (is_numeric($time)) {
            if ($time <= 0) {
                /**
                 * 5 years, however memcached or memory cached will gone when u restart it
                 * just recommended for sqlite. files
                 */
                $time = 30 * 24 * 3600 * 5;
            }
            $this->expirationDate = (new \DateTime())->add(new \DateInterval(sprintf('PT%dS', $time)));
        } else if ($time instanceof \DateInterval) {
            $this->expirationDate = (new \DateTime())->add($time);
        } else {
            throw new \InvalidArgumentException('Invalid date format');
        }

        return $this;
    }
}
