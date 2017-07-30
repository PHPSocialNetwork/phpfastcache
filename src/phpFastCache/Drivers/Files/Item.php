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

namespace phpFastCache\Drivers\Files;

use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Core\Item\ItemBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Drivers\Files\Driver as FilesDriver;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;

/**
 * Class Item
 * @package phpFastCache\Drivers\Files
 */
class Item implements ExtendedCacheItemInterface
{
    use ItemBaseTrait;

    /**
     * Item constructor.
     * @param \phpFastCache\Drivers\Files\Driver $driver
     * @param $key
     * @throws phpFastCacheInvalidArgumentException
     */
    public function __construct(FilesDriver $driver, $key)
    {
        if (is_string($key)) {
            $this->expirationDate = new \DateTime();
            $this->key = $key;
            $this->driver = $driver;
            $this->driver->setItem($this);
        } else {
            throw new phpFastCacheInvalidArgumentException(sprintf('$key must be a string, got type "%s" instead.', get_class($key)));
        }
    }

    /**
     * @param ExtendedCacheItemPoolInterface $driver
     * @throws phpFastCacheInvalidArgumentException
     * @return static
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver)
    {
        if ($driver instanceof FilesDriver) {
            $this->driver = $driver;

            return $this;
        } else {
            throw new phpFastCacheInvalidArgumentException('Invalid driver instance');
        }
    }
}