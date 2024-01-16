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

namespace Phpfastcache\Event\Event;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\Event\EventsInterface;

class CacheItemExpireAtEvent extends AbstractItemEvent
{
    public const EVENT_NAME = EventsInterface::CACHE_ITEM_EXPIRE_AT;

    public function __construct(ExtendedCacheItemInterface $item, protected \DateTimeInterface $expireAt)
    {
        parent::__construct($item);
    }

    /**
     * @return \DateTimeInterface
     */
    public function getExpireAt(): \DateTimeInterface
    {
        return $this->expireAt;
    }
}
