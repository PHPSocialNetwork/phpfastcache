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

use phpFastCache\Core\DriverAbstract;

trait ItemBaseTrait
{
    /**
     * @var bool
     */
    protected $fetched = false;

    /**
     * @var DriverAbstract
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
            throw new phpFastCacheInvalidArgumentException('$expiration must be an object implementing the DateTimeInterface got: ' . gettype($expiration));
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

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return string
     */
    public function getEncodedKey()
    {
        return md5($this->getKey());
    }

    /**
     * @return mixed
     */
    public function getUncommittedData()
    {
        return $this->data;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return max(0, $this->expirationDate->getTimestamp() - time());
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return $this->expirationDate->getTimestamp() < (new \DateTime())->getTimestamp();
    }

    /**
     * @param int $step
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function increment($step = 1)
    {
        if (is_int($step)) {
            $this->fetched = true;
            $this->data += $step;
        } else {
            throw new \InvalidArgumentException('$step must be numeric.');
        }

        return $this;
    }

    /**
     * @param int $step
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function decrement($step = 1)
    {
        if (is_int($step)) {
            $this->fetched = true;
            $this->data -= $step;
        } else {
            throw new \InvalidArgumentException('$step must be numeric.');
        }

        return $this;
    }

    /**
     * @param array|string $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function append($data)
    {
        if (is_array($this->data)) {
            array_push($this->data, $data);
        } else if (is_string($data)) {
            $this->data .= (string) $data;
        } else {
            throw new \InvalidArgumentException('$data must be either array nor string.');
        }

        return $this;
    }


    /**
     * @param array|string $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function prepend($data)
    {
        if (is_array($this->data)) {
            array_unshift($this->data, $data);
        } else if (is_string($data)) {
            $this->data = (string) $data . $this->data;
        } else {
            throw new \InvalidArgumentException('$data must be either array nor string.');
        }

        return $this;
    }

    /**
     * @param $tagName
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addTag($tagName)
    {
        if (is_string($tagName)) {
            $this->tags = array_unique(array_merge($this->tags, [$tagName]));

            return $this;
        } else {
            throw new \InvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @param array $tagNames
     * @return $this
     */
    public function addTags(array $tagNames)
    {
        foreach ($tagNames as $tagName) {
            $this->addTag($tagName);
        }

        return $this;
    }

    /**
     * @param array $tags
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTags(array $tags)
    {
        if (count($tags)) {
            if (array_filter($tags, 'is_string')) {
                $this->tags = $tags;
            } else {
                throw new \InvalidArgumentException('$tagName must be an array of string');
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return string
     */
    public function getTagsAsString($separator = ', ')
    {
        return implode($separator, $this->tags);
    }

    /**
     * @param $tagName
     * @return $this
     */
    public function removeTag($tagName)
    {
        if (($key = array_search($tagName, $this->tags)) !== false) {
            unset($this->tags[ $key ]);
            $this->removedTags[] = $tagName;
        }

        return $this;
    }

    /**
     * @param array $tagNames
     * @return $this
     */
    public function removeTags(array $tagNames)
    {
        foreach ($tagNames as $tagName) {
            $this->removeTag($tagName);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getRemovedTags()
    {
        return array_diff($this->removedTags, $this->tags);
    }

    /**
     * Return the data as a well-formatted string.
     * Any scalar value will be casted to an array
     * @param int $option json_encode() options
     * @param int $depth json_encode() depth
     * @return string
     */
    public function getDataAsJsonString($option = 0, $depth = 512)
    {
        $data = $this->get();

        if (is_object($data) || is_array($data)) {
            $data = json_encode($data, $option, $depth);
        } else {
            $data = json_encode([$data], $option, $depth);
        }

        return json_encode($data, $option, $depth);
    }

    /**
     * Implements \JsonSerializable interface
     * @return mixed
     */
    public function jsonSerialize()
    {
        return $this->get();
    }

    /**
     * Prevent recursions for Debug (php 5.6+)
     * @return array
     */
    final public function __debugInfo()
    {
        $info = get_object_vars($this);
        $info[ 'driver' ] = 'object(' . get_class($info[ 'driver' ]) . ')';

        return (array) $info;
    }
}
