<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
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
    protected string $itemKey;

    protected DateTimeInterface $itemDate;

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
