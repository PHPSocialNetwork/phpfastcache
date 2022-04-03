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

namespace Phpfastcache\Event;

use Phpfastcache\Helper\UninstanciableObjectTrait;

class Event implements EventInterface
{
    use UninstanciableObjectTrait;

    public const CACHE_GET_ITEM = 'CacheGetItem';
    public const CACHE_DELETE_ITEM = 'CacheDeleteItem';
    public const CACHE_SAVE_ITEM = 'CacheSaveItem';
    public const CACHE_SAVE_MULTIPLE_ITEMS = 'CacheSaveMultipleItems';
    public const CACHE_SAVE_DEFERRED_ITEM = 'CacheSaveDeferredItem';
    public const CACHE_COMMIT_ITEM = 'CacheCommitItem';
    public const CACHE_CLEAR_ITEM = 'CacheClearItem';
    public const CACHE_WRITE_FILE_ON_DISK = 'CacheWriteFileOnDisk';
    public const CACHE_GET_ITEM_IN_SLAM_BATCH = 'CacheGetItemInSlamBatch';
    public const CACHE_REPLICATION_SLAVE_FALLBACK = 'CacheReplicationSlaveFallback';
    public const CACHE_REPLICATION_RANDOM_POOL_CHOSEN = 'CacheReplicationRandomPoolChosen';
    public const CACHE_CLUSTER_BUILT = 'CacheClusterBuilt';
    public const CACHE_ITEM_SET = 'CacheItemSet';
    public const CACHE_ITEM_EXPIRE_AT = 'CacheItemExpireAt';
    public const CACHE_ITEM_EXPIRE_AFTER = 'CacheItemExpireAfter';

    public static function getEvents(): array
    {
        return (new \ReflectionClass(static::class))->getConstants();
    }
}
