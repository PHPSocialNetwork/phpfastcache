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

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventsInterface;

class CacheWriteFileOnDiskItemPoolEvent extends AbstractItemPoolEvent
{
    public const EVENT_NAME = EventsInterface::CACHE_WRITE_FILE_ON_DISK;

    public function __construct(ExtendedCacheItemPoolInterface $cachePool, protected string $file)
    {
        parent::__construct($cachePool);
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }
}
