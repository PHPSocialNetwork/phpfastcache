<?php

declare(strict_types=1);

namespace Phpfastcache\Drivers\Couchbasev4;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Item\TaggableCacheItemTrait;

class Item implements ExtendedCacheItemInterface
{
    use TaggableCacheItemTrait;

    protected function getDriverClass(): string
    {
        return Driver::class;
    }
}
