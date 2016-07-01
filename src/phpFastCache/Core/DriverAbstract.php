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

namespace phpFastCache\Core;

use phpFastCache\Cache\DriverBaseTrait;
use phpFastCache\Cache\ExtendedCacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

/**
 * Class DriverAbstract
 * @package phpFastCache\Core
 */
abstract class DriverAbstract implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

    const DRIVER_CHECK_FAILURE      = '%s is not installed or is misconfigured, cannot continue.';
    const DRIVER_TAGS_KEY_PREFIX    = '_TAG_';
    const DRIVER_DATA_WRAPPER_INDEX = 'd';
    const DRIVER_TIME_WRAPPER_INDEX = 't';
    const DRIVER_TAGS_WRAPPER_INDEX = 'g';

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return array [
     *      'd' => 'THE ITEM DATA'
     *      't' => 'THE ITEM DATE EXPIRATION'
     *      'g' => 'THE ITEM TAGS'
     * ]
     *
     */
    abstract protected function driverRead(CacheItemInterface $item);

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    abstract protected function driverWrite(CacheItemInterface $item);

    /**
     * @return bool
     */
    abstract protected function driverClear();

    /**
     * @return bool
     */
    abstract protected function driverConnect();

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     */
    abstract protected function driverDelete(CacheItemInterface $item);
}