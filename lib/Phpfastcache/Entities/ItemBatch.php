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

namespace Phpfastcache\Entities;

use DateTimeInterface;

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
     * @var DateTimeInterface
     */
    protected $itemDate;

    /**
     * ItemBatch constructor.
     * @param string $itemKey
     * @param DateTimeInterface $itemDate
     */
    public function __construct(string $itemKey, DateTimeInterface $itemDate)
    {
        $this->itemKey = $itemKey;
        $this->itemDate = $itemDate;
    }

    /**
     * @return string
     */
    public function getItemKey(): string
    {
        return $this->itemKey;
    }

    /**
     * @return DateTimeInterface
     */
    public function getItemDate(): DateTimeInterface
    {
        return $this->itemDate;
    }
}