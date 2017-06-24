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

namespace phpFastCache\Entities;

use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;

/**
 * Class ItemBatch
 * @package phpFastCache\Entities
 */
class ItemBatch
{
    /**
     * @var string
     */
    protected $itemKey;

    /**
     * @var \DateTime
     */
    protected $itemDate;

    /**
     * ItemBatch constructor.
     * @param $itemKey
     * @param \DateTime $itemDate
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidArgumentException
     */
    public function __construct($itemKey, \DateTime $itemDate)
    {
        if (is_string($itemKey)) {
            $this->itemKey = $itemKey;
            $this->itemDate = $itemDate;
        } else {
            throw new phpFastCacheInvalidArgumentException(sprintf('$itemKey must be a string, got "%s" instead', gettype($itemKey)));
        }
    }

    /**
     * @return string
     */
    public function getItemKey()
    {
        return $this->itemKey;
    }

    /**
     * @return \DateTime
     */
    public function getItemDate()
    {
        return $this->itemDate;
    }
}