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

namespace Phpfastcache\Core\Pool;

use InvalidArgumentException;
use Phpfastcache\Config\ConfigurationOptionInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventManagerDispatcherInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Exceptions\PhpfastcacheUnsupportedMethodException;
use Phpfastcache\Util\ClassNamespaceResolverInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Extended cache item pool interface that
 * contains all the phpfastcache-related
 * methods that does not belong to PSR-6.
 */
interface ExtendedCacheItemPoolInterface extends CacheItemPoolInterface, EventManagerDispatcherInterface, ClassNamespaceResolverInterface, TaggableCacheItemPoolInterface
{
    public const DRIVER_CHECK_FAILURE = '%s is not installed or is misconfigured, cannot continue. 
    Also, please verify the suggested dependencies in composer because as of the V6, 3rd party libraries are no longer required.%s';

    public const DRIVER_CONNECT_FAILURE = '%s failed to connect with the following error message: "%s" line %d in %s.';

    public const DRIVER_KEY_WRAPPER_INDEX = 'k';

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
     * Hard-limit count  of items returns by getAllItems()
     */
    public const MAX_ALL_KEYS_COUNT = 9999;

    /**
     * Return the config class name
     * @return string
     */
    public static function getConfigClass(): string;

    /**
     * Return the item class name
     * @return string
     */
    public static function getItemClass(): string;

    /**
     * @param string $key
     * @return string
     */
    public function getEncodedKey(string $key): string;

    /**
     * @return ConfigurationOptionInterface
     */
    public function getConfig(): ConfigurationOptionInterface;

    /**
     * @return ConfigurationOptionInterface
     */
    public function getDefaultConfig(): ConfigurationOptionInterface;

    /**
     * @return string
     */
    public function getDriverName(): string;

    /**
     * @return mixed
     */
    public function getInstanceId(): string;

    /**
     * [Phpfastcache phpDoc Override]
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
    public function getItem(string $key): ExtendedCacheItemInterface;

    /**
     * [Phpfastcache phpDoc Override]
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     * An indexed array of keys of items to retrieve.
     *
     * @return iterable<ExtendedCacheItemInterface>
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function getItems(array $keys = []): iterable;

    /**
     * Returns the WHOLE cache as a traversable set of cache items.
     * A hard-limit of 9999 items is defined internally to prevent
     * serious performances issues of your application.
     * @see ExtendedCacheItemPoolInterface::MAX_ALL_KEYS_COUNT
     *
     * @param string $pattern
     * An optional pattern supported by a limited range of drivers.
     * If this parameter is unsupported by the driver, a PhpfastcacheInvalidArgumentException will be thrown.
     *
     * @return iterable<ExtendedCacheItemInterface>
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. However, if no keys are returned by the backend then an empty
     *   traversable WILL be returned instead.
     *
     * @throws PhpfastcacheInvalidArgumentException If the driver does not support the $pattern argument
     * @throws PhpfastcacheUnsupportedMethodException If the driver does not permit to list all the keys through this implementation.
     */
    public function getAllItems(string $pattern = ''): iterable;

    /**
     * Returns A json string that represents an array of items.
     *
     * @param array<string> $keys An indexed array of keys of items to retrieve.
     * @param int $options \json_encode() options
     * @param int $depth \json_encode() depth
     *
     * @return string
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function getItemsAsJsonString(array $keys = [], int $options = \JSON_THROW_ON_ERROR, int $depth = 512): string;

    public function setItem(CacheItemInterface $item): static;

    public function getStats(): DriverStatistic;

    /**
     * Get a quick help guide
     * about the current driver
     */
    public function getHelp(): string;

    public function detachItem(CacheItemInterface $item): static;

    public function detachAllItems(): static;

    public function attachItem(CacheItemInterface $item): static;

    /**
     * Returns true if the item exists, is attached and the Spl Hash matches
     * Returns false if the item exists, is attached and the Spl Hash mismatches
     * Returns null if the item does not exist
     *
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheLogicException
     */
    public function isAttached(CacheItemInterface $item): bool;

    /**
     * Persists a cache item immediately.
     *
     * @param ExtendedCacheItemInterface|CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(ExtendedCacheItemInterface|CacheItemInterface $item): bool;

    /**
     * Save multiple items, possible uses:
     *  saveMultiple([$item1, $item2, $item3]);
     *  saveMultiple($item1, $item2, $item3);
     *
     * @param ExtendedCacheItemInterface[] $items
     * @return bool
     */
    public function saveMultiple(ExtendedCacheItemInterface ...$items): bool;

    /**
     * @return DriverIO
     */
    public function getIO(): DriverIO;
}
