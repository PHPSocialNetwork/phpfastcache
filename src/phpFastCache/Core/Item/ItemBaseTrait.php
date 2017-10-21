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

use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;

/**
 * Trait ItemBaseTrait
 * @package phpFastCache\Core\Item
 */
trait ItemBaseTrait
{
    use ItemExtendedTrait;

    /**
     * @var bool
     */
    protected $fetched = false;

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

        /**
         * @eventName CacheSaveDeferredItem
         * @param ExtendedCacheItemInterface $this
         * @param mixed $value
         *
         */
        $this->eventManager->dispatch('CacheItemSet', $this, $value);

        return $this;
    }

    /**
     * @return bool
     */
    public function isHit()
    {
        return $this->isHit;
    }

    /**
     * @param bool $isHit
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
     */
    public function setHit($isHit)
    {
        if (is_bool($isHit)) {
            $this->isHit = $isHit;

            return $this;
        } else {
            throw new phpFastCacheInvalidArgumentException('$isHit must be a boolean');
        }
    }

    /**
     * @param \DateTimeInterface $expiration
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
     */
    public function expiresAt($expiration)
    {
        if ($expiration instanceof \DateTimeInterface) {
            /**
             * @eventName CacheItemExpireAt
             * @param ExtendedCacheItemInterface $this
             * @param \DateTimeInterface $expiration
             */
            $this->eventManager->dispatch('CacheItemExpireAt', $this, $expiration);
            $this->expirationDate = $expiration;
        } else {
            throw new phpFastCacheInvalidArgumentException('$expiration must be an object implementing the DateTimeInterface got: ' . gettype($expiration));
        }

        return $this;
    }

    /**
     * @param \DateInterval|int $time
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
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

            /**
             * @eventName CacheItemExpireAt
             * @param ExtendedCacheItemInterface $this
             * @param \DateTimeInterface $expiration
             */
            $this->eventManager->dispatch('CacheItemExpireAfter', $this, $time);

            $this->expirationDate = (new \DateTime())->add(new \DateInterval(sprintf('PT%dS', $time)));
        } else if ($time instanceof \DateInterval) {
            /**
             * @eventName CacheItemExpireAt
             * @param ExtendedCacheItemInterface $this
             * @param \DateTimeInterface $expiration
             */
            $this->eventManager->dispatch('CacheItemExpireAfter', $this, $time);

            $this->expirationDate = (new \DateTime())->add($time);
        } else {
            throw new phpFastCacheInvalidArgumentException('Invalid date format');
        }

        return $this;
    }
}
