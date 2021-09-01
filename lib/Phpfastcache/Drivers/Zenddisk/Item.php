<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Lucas Brucksch <support@hammermaps.de>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Zenddisk;

use Phpfastcache\Core\Item\{ExtendedCacheItemInterface, ItemBaseTrait};

/**
 * Class Item
 * @package phpFastCache\Drivers\Zenddisk
 */
class Item implements ExtendedCacheItemInterface
{
    use ItemBaseTrait;

    protected function getDriverClass(): string
    {
        return Driver::class;
    }
}
