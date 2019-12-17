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

namespace Phpfastcache\Core\Pool;

use InvalidArgumentException;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventInterface;
use Phpfastcache\Exceptions\{
    PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException
};
use Psr\Cache\{
    CacheItemInterface, CacheItemPoolInterface
};


/**
 * Interface ExtendedCacheItemPoolInterface
 *
 * IMPORTANT NOTICE
 *
 * If you modify this file please make sure that
 * the ActOnAll helper will also get those modifications
 * since it does no longer implements this interface
 * @see \Phpfastcache\Helper\ActOnAll
 *
 * @package phpFastCache\Core\Pool
 */
interface AggregablePoolInterface
{

}
