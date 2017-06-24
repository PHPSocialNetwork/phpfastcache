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

namespace phpFastCache\Drivers\Wincache;

use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Core\Item\ItemBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Drivers\Wincache\Driver as WincacheDriver;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;

/**
 * Class Item
 * @package phpFastCache\Drivers\Wincache
 */
class Item implements ExtendedCacheItemInterface
{
    use ItemBaseTrait;

    /**
     * Item constructor.
     * @param \phpFastCache\Drivers\Wincache\Driver $driver
     * @param $key
     * @throws phpFastCacheInvalidArgumentException
     */
    public function __construct(WincacheDriver $driver, $key)
    {
        if (is_string($key)) {
            $this->key = $key;
            $this->driver = $driver;
            $this->driver->setItem($this);
            $this->expirationDate = new \DateTime();
        } else {
            throw new phpFastCacheInvalidArgumentException(sprintf('$key must be a string, got type "%s" instead.', gettype($key)));
        }
    }

    /**
     * @param ExtendedCacheItemPoolInterface $driver
     * @throws phpFastCacheInvalidArgumentException
     * @return static
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver)
    {
        if ($driver instanceof WincacheDriver) {
            $this->driver = $driver;

            return $this;
        } else {
            throw new phpFastCacheInvalidArgumentException('Invalid driver instance');
        }
    }
}