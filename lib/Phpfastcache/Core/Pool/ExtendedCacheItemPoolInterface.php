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
interface ExtendedCacheItemPoolInterface extends CacheItemPoolInterface
{
    const DRIVER_CHECK_FAILURE = '%s is not installed or is misconfigured, cannot continue. 
    Also, please verify the suggested dependencies in composer because as of the V6, 3rd party libraries are no longer required.';

    const DRIVER_CONNECT_FAILURE = '%s failed to connect with the following error message: "%s" line %d in %s';

    const DRIVER_TAGS_KEY_PREFIX = '_TAG_';
    const DRIVER_TAGS_WRAPPER_INDEX = 'g';
    const DRIVER_DATA_WRAPPER_INDEX = 'd';

    /**
     * Expiration date Index
     */
    const DRIVER_EDATE_WRAPPER_INDEX = 'e';

    /**
     * Creation date Index
     */
    const DRIVER_CDATE_WRAPPER_INDEX = 'c';

    /**
     * Modification date Index
     */
    const DRIVER_MDATE_WRAPPER_INDEX = 'm';


    /**
     * @return ConfigurationOption
     */
    public function getConfig(): ConfigurationOption;

    /**
     * @return ConfigurationOption
     */
    public function getDefaultConfig(): ConfigurationOption;

    /**
     * @param string $optionName
     * @return mixed
     */
    public function getConfigOption($optionName);

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
     * @throws PhpfastcacheInvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return ExtendedCacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem($key);

    /**
     * [phpFastCache phpDoc Override]
     * Returns a traversable set of cache items.
     *
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return ExtendedCacheItemInterface[]
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
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
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return string
     */
    public function getItemsAsJsonString(array $keys = [], $option = 0, $depth = 512): string;

    /**
     * @param \Psr\Cache\CacheItemInterface $item
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
     * Returns a traversable set of cache items by a tag name.
     *
     * @param string $tagName
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return ExtendedCacheItemInterface[]
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItemsByTag($tagName): array;

    /**
     * Returns a traversable set of cache items by one of multiple tag names.
     *
     * @param string[] $tagNames
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return ExtendedCacheItemInterface[]
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItemsByTags(array $tagNames): array;

    /**
     * Returns a traversable set of cache items by all of multiple tag names.
     *
     * @param string[] $tagNames
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return ExtendedCacheItemInterface[]
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItemsByTagsAll(array $tagNames): array;

    /**
     * Returns A json string that represents an array of items by tags-based.
     *
     * @param string[] $tagNames
     * An indexed array of keys of items to retrieve.
     * @param int $option \json_encode() options
     * @param int $depth \json_encode() depth
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return string
     */
    public function getItemsByTagsAsJsonString(array $tagNames, $option = 0, $depth = 512): string;

    /**
     * Removes the item from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to delete
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItemsByTag($tagName): bool;

    /**
     * Removes the item from the pool by one of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to delete
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItemsByTags(array $tagNames): bool;

    /**
     * Removes the item from the pool by all of multiple tag names.
     *
     * @param string[] $tagNames
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItemsByTagsAll(array $tagNames): bool;

    /**
     * Increment the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to increment
     *
     * @param int $step
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully incremented. False if there was an error.
     */
    public function incrementItemsByTag($tagName, $step = 1): bool;

    /**
     * Increment the items from the pool by one of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to increment
     *
     * @param int $step
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully incremented. False if there was an error.
     */
    public function incrementItemsByTags(array $tagNames, $step = 1): bool;

    /**
     * Increment the items from the pool by all of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to increment
     *
     * @param int $step
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully incremented. False if there was an error.
     */
    public function incrementItemsByTagsAll(array $tagNames, $step = 1): bool;

    /**
     * Decrement the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to decrement
     *
     * @param int $step
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully decremented. False if there was an error.
     */
    public function decrementItemsByTag($tagName, $step = 1): bool;

    /**
     * Decrement the items from the pool by one of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to decrement
     *
     * @param int $step
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully decremented. False if there was an error.
     */
    public function decrementItemsByTags(array $tagNames, $step = 1): bool;

    /**
     * Decrement the items from the pool by all of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to decrement
     *
     * @param int $step
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully decremented. False if there was an error.
     */
    public function decrementItemsByTagsAll(array $tagNames, $step = 1): bool;

    /**
     * Decrement the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to append
     *
     * @param array|string $data
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully appended. False if there was an error.
     */
    public function appendItemsByTag($tagName, $data): bool;

    /**
     * Append the items from the pool by one of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to append
     *
     * @param array|string $data
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully appended. False if there was an error.
     */
    public function appendItemsByTags(array $tagNames, $data): bool;

    /**
     * Append the items from the pool by all of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to append
     *
     * @param array|string $data
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully appended. False if there was an error.
     */
    public function appendItemsByTagsAll(array $tagNames, $data): bool;

    /**
     * Prepend the items from the pool by tag.
     *
     * @param string $tagName
     *   The tag for which to prepend
     *
     * @param array|string $data
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully prepended. False if there was an error.
     */
    public function prependItemsByTag($tagName, $data): bool;

    /**
     * Prepend the items from the pool by one of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to prepend
     *
     * @param array|string $data
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully prepended. False if there was an error.
     */
    public function prependItemsByTags(array $tagNames, $data): bool;

    /**
     * Prepend the items from the pool by all of multiple tag names.
     *
     * @param string[] $tagNames
     *   The tag for which to prepend
     *
     * @param array|string $data
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a phpfastcacheInvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully prepended. False if there was an error.
     */
    public function prependItemsByTagsAll(array $tagNames, $data): bool;

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return void
     */
    public function detachItem(CacheItemInterface $item);

    /**
     * @return void
     */
    public function detachAllItems();

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return void
     * @throws PhpfastcacheLogicException
     */
    public function attachItem(CacheItemInterface $item);

    /**
     * Returns true if the item exists, is attached and the Spl Hash matches
     * Returns false if the item exists, is attached and the Spl Hash mismatches
     * Returns null if the item does not exists
     *
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool|null
     * @throws PhpfastcacheLogicException
     */
    public function isAttached(CacheItemInterface $item);


    /**
     * Set the EventManager instance
     *
     * @param EventInterface $em
     * @return self
     */
    public function setEventManager(EventInterface $em): self;

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
     * Defines if the driver is allowed
     * to be used in "Auto" driver.
     *
     * @return bool
     */
    public static function isUsableInAutoContext(): bool;

    /**
     * Return the config class name
     * @return string
     */
    public static function getConfigClass(): string;
}