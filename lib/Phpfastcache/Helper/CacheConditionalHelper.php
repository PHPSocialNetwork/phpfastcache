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

namespace Phpfastcache\Helper;

use Phpfastcache\CacheContract;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @deprecated Use \Phpfastcache\CacheContract instead
 */
class CacheConditionalHelper extends CacheContract
{
    /**
     * CacheConditionalHelper constructor.
     * @param CacheItemPoolInterface $cacheInstance
     */
    public function __construct(CacheItemPoolInterface $cacheInstance)
    {
        \trigger_error(
            \sprintf(
                'Class "%s" is deprecated, use "%s" class instead. See the documentation about this change here: %s',
                self::class,
                parent::class,
                'https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV9%CB%96%5D-Cache-contract'
            ),
            E_USER_DEPRECATED
        );
        parent::__construct($cacheInstance);
    }
}
