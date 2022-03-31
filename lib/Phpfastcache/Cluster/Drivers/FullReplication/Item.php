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

namespace Phpfastcache\Cluster\Drivers\FullReplication;

use Phpfastcache\Cluster\ItemAbstract;

class Item extends ItemAbstract
{
    protected function getDriverClass(): string
    {
        return Driver::class;
    }
}
