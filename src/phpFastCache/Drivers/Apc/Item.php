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

namespace phpFastCache\Drivers\Apc;

use phpFastCache\Cache\ExtendedCacheItemInterface;
use phpFastCache\Cache\ExtendedCacheItemPoolInterface;
use phpFastCache\Cache\ItemBaseTrait;
use phpFastCache\Drivers\Apc\Driver as ApcDriver;

/**
 * Class Item
 * @package phpFastCache\Drivers\Apc
 */
class Item implements ExtendedCacheItemInterface
{
    use ItemBaseTrait;

    /**
     * Item constructor.
     * @param \phpFastCache\Drivers\Apc\Driver $driver
     * @param $key
     * @throws \InvalidArgumentException
     */
    public function __construct(ApcDriver $driver, $key)
    {
        if (is_string($key)) {
            $this->key = $key;
            $this->driver = $driver;
            $this->driver->setItem($this);
            $this->expirationDate = new \DateTime();
        } else {
            throw new \InvalidArgumentException(sprintf('$key must be a string, got type "%s" instead.', gettype($key)));
        }
    }

    /**
     * @param ExtendedCacheItemPoolInterface $driver
     * @throws \InvalidArgumentException
     * @return static
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver)
    {
        if ($driver instanceof ApcDriver) {
            $this->driver = $driver;

            return $this;
        } else {
            throw new \InvalidArgumentException('Invalid driver instance');
        }
    }
}