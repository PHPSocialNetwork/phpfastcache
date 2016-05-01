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
     * @var \DateTimeInterface
     */
    protected $expirationDate;

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
     * @throws \InvalidArgumentException
     */
    public function get()
    {
/*        if (!$this->fetched) {
            $this->data = $this->driver->driverRead($this->getKey());
        }*/

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
        return $this->driver->driverIsHit($this);
    }

    /**
     * @param \DateTimeInterface $expiration
     * @return $this
     */
    public function expiresAt($expiration)
    {
        if ($expiration instanceof \DateTimeInterface) {
            $this->expirationDate = $expiration;
        }

        return $this;
    }

    /**
     * @param \DateInterval|int $time
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function expiresAfter($time)
    {
        if (is_numeric($time) && $time > 0) {
            $this->expirationDate = $this->expirationDate->add(new \DateInterval(sprintf('PT%dS', $time)));
        } else if ($time instanceof \DateInterval) {
            $this->expirationDate = $this->expirationDate->add($time);
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

    public function getTtl()
    {
        return $this->expirationDate->getTimestamp() - time();
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return $this->expirationDate->getTimestamp() < (new \DateTime())->getTimestamp();
    }

    /**
     * @return $this
     */
    public function increment($step = 1)
    {
        if(is_int($step)){
            $this->fetched = true;
            $this->data += $step;
        }else{
            throw new \InvalidArgumentException('$step must be numeric.');
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function decrement($step = 1)
    {
        if(is_int($step)){
            $this->fetched = true;
            $this->data -= $step;
        }else{
            throw new \InvalidArgumentException('$step must be numeric.');
        }
        return $this;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $vars = (array) array_keys(get_object_vars($this));
        // Remove unneeded vars
        //unset($vars[array_search('driver', $vars)]);
        return $vars;
    }
}