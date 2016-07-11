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

/**
 * Class ItemExtendedTrait
 * @package phpFastCache\Core\Item
 */
trait ItemExtendedTrait
{
    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/


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
    public function setExpirationDate(\DateTimeInterface $expiration)
    {
        return $this->expiresAt($expiration);
    }


    /**
     * @return \DateTimeInterface
     * @throws \LogicException
     */
    public function getCreationDate()
    {
        if($this->driver->getConfig()['itemDetailedDate']){
            return $this->creationDate;
        }else{
            throw new \LogicException('Cannot access to the creation date when the "itemDetailedDate" configuration is disabled.');
        }
    }

    /**
     * @param \DateTimeInterface $date
     * @return $this
     * @throws \LogicException
     */
    public function setCreationDate(\DateTimeInterface $date)
    {
        if($this->driver->getConfig()['itemDetailedDate']){
            $this->creationDate = $date;
            return $this;
        }else{
            throw new \LogicException('Cannot access to the creation date when the "itemDetailedDate" configuration is disabled.');
        }
    }

    /**
     * @return \DateTimeInterface
     * @throws \LogicException
     */
    public function getModificationDate()
    {
        if($this->driver->getConfig()['itemDetailedDate']){
            return $this->modificationDate;
        }else{
            throw new \LogicException('Cannot access to the modification date when the "itemDetailedDate" configuration is disabled.');
        }
    }

    /**
     * @param \DateTimeInterface $date
     * @return $this
     * @throws \LogicException
     */
    public function setModificationDate(\DateTimeInterface $date)
    {
        if($this->driver->getConfig()['itemDetailedDate']){
            $this->modificationDate = $date;
            return $this;
        }else{
            throw new \LogicException('Cannot access to the creation date when the "itemDetailedDate" configuration is disabled.');
        }
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        $ttl = $this->expirationDate->getTimestamp() - time();
        if ($ttl > 2592000) {
            $ttl = time() + $ttl;
        }

        return $ttl;
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