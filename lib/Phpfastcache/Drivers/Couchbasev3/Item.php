<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Couchbasev3;

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Drivers\Couchbase\Item as CoubaseV2Item;
use Phpfastcache\Drivers\Couchbasev3\Driver as CouchbaseDriver;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

/**
 * Class Item
 * @package phpFastCache\Drivers\Couchbase
 */
class Item extends CoubaseV2Item
{
    /**
     * Item constructor.
     * @param Driver $driver
     * @param $key
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function __construct(CouchbaseDriver $driver, $key)
    {
        parent::__construct($driver, $key);
    }

    /**
     * @param ExtendedCacheItemPoolInterface $driver
     * @return static
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver)
    {
        if ($driver instanceof CouchbaseDriver) {
            $this->driver = $driver;

            return $this;
        }

        throw new PhpfastcacheInvalidArgumentException('Invalid driver instance');
    }
}
