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
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventManagerDispatcherInterface;
use Phpfastcache\Exceptions\{PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException};
use Phpfastcache\Util\ClassNamespaceResolverInterface;
use Psr\Cache\{CacheItemInterface, CacheItemPoolInterface};


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
interface ExtendedCacheItemPoolInterface extends CacheItemPoolInterface, EventManagerDispatcherInterface, ClassNamespaceResolverInterface, TaggableCacheItemPoolInterface
{
    public const DRIVER_CHECK_FAILURE = '%s is not installed or is misconfigured, cannot continue. 
    Also, please verify the suggested dependencies in composer because as of the V6, 3rd party libraries are no longer required.';

    public const DRIVER_CONNECT_FAILURE = '%s failed to connect with the following error message: "%s" line %d in %s';

    public const DRIVER_DATA_WRAPPER_INDEX = 'd';

    /**
     * Expiration date Index
     */
    public const DRIVER_EDATE_WRAPPER_INDEX = 'e';

    /**
     * Creation date Index
     */
    public const DRIVER_CDATE_WRAPPER_INDEX = 'c';

    /**
     * Modification date Index
     */
    public const DRIVER_MDATE_WRAPPER_INDEX = 'm';

    /**
     * Return the config class name
     * @return string
     */
    public static function getConfigClass(): string;

    /**
     * @return ConfigurationOption
     */
    public function getConfig(): ConfigurationOption;

    /**
     * @return ConfigurationOption
     */
    public function getDefaultConfig(): ConfigurationOption;

    /**
     * @return string
     */
    public function getDriverName(): string;

    /**
     * @return mixed
     */
    public function getInstanceId(): string;

    /**
     * [phpFastCache phpDoc Override]
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @return ExtendedCacheItemInterface
     *   The corresponding Cache Item.
     * @throws PhpfastcacheInvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function getItem($key);

    /**
     * [phpFastCache phpDoc Override]
     * Returns a traversable set of cache items.
     *
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     *
     * @return ExtendedCacheItemInterface[]
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function getItems(array $keys = []);

    /**
     * Returns A json string that represents an array of items.
     *
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     * @param int $option \json_encode() options
     * @param int $depth \json_encode() depth
     *
     * @return string
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function getItemsAsJsonString(array $keys = [], int $option = 0, int $depth = 512): string;

    /**
     * @param CacheItemInterface $item
     * @return mixed
     */
    public function setItem(CacheItemInterface $item);

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic;

    /**
     * Get a quick help guide
     * about the current driver
     *
     * @return string
     */
    public function getHelp(): string;

    /**
     * @param CacheItemInterface $item
     * @return void
     */
    public function detachItem(CacheItemInterface $item);

    /**
     * @return void
     */
    public function detachAllItems();

    /**
     * @param CacheItemInterface $item
     * @return void
     * @throws PhpfastcacheLogicException
     */
    public function attachItem(CacheItemInterface $item);

    /**
     * Returns true if the item exists, is attached and the Spl Hash matches
     * Returns false if the item exists, is attached and the Spl Hash mismatches
     * Returns null if the item does not exists
     *
     * @param CacheItemInterface $item
     * @return bool|null
     * @throws PhpfastcacheLogicException
     */
    public function isAttached(CacheItemInterface $item);

    /**
     * Save multiple items, possible uses:
     *  saveMultiple([$item1, $item2, $item3]);
     *  saveMultiple($item1, $item2, $item3);
     *
     * @param ExtendedCacheItemInterface[] $items
     * @return bool
     */
    public function saveMultiple(...$items): bool;


    /**
     * @return DriverIO
     */
    public function getIO(): DriverIO;
}
