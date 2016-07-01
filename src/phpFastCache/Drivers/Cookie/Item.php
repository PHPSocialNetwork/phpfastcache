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

namespace phpFastCache\Drivers\Cookie;

use phpFastCache\Cache\ExtendedCacheItemInterface;
use phpFastCache\Cache\ExtendedCacheItemPoolInterface;
use phpFastCache\Cache\ItemBaseTrait;
use phpFastCache\Drivers\Cookie\Driver as CookieDriver;

/**
 * Class Item
 * @package phpFastCache\Drivers\Apc
 */
class Item implements ExtendedCacheItemInterface
{
    use ItemBaseTrait;

    /**
     * Item constructor.
     * @param \phpFastCache\Drivers\Cookie\Driver $driver
     * @param $key
     * @throws \InvalidArgumentException
     */
    public function __construct(CookieDriver $driver, $key)
    {
        if (is_string($key)) {
            $this->expirationDate = new \DateTime();
            $this->key = $key;
            $this->driver = $driver;
            $this->driver->setItem($this);
        } else {
            throw new \InvalidArgumentException(sprintf('$key must be a string, got type "%s" instead.',
              gettype($key)));
        }
    }


    /**
     * @param ExtendedCacheItemPoolInterface $driver
     * @throws \InvalidArgumentException
     * @return static
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver)
    {
        if ($driver instanceof CookieDriver) {
            $this->driver = $driver;

            return $this;
        } else {
            throw new \InvalidArgumentException('Invalid driver instance');
        }
    }
}