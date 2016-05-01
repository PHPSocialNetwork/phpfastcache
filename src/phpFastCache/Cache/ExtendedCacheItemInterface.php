<?php
namespace phpFastCache\Cache;

use Psr\Cache\CacheItemInterface;

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
     * @return int
     */
    public function increment();

    /**
     * @return int
     */
    public function decrement();
}