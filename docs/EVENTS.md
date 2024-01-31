:mega: As of the V6, Phpfastcache provides an event mechanism.
You can subscribe to an event by passing a Closure/Callable to an active event:

```php
use Phpfastcache\EventManager;

/**
* Bind the listener
*/
use Phpfastcache\Event\EventsInterface;
use Phpfastcache\Event\Event\CacheGetItemEvent;

EventManager::getInstance()->addListener(EventsInterface::CACHE_GET_ITEM, static function(CacheGetItemEvent $event){
    $event->getCacheItem()->set('[HACKED BY EVENT] ' . $item->get());
});

```

~~An event callback can get unbind, but you MUST provide a name to the callback previously:~~
**/!\ Method `unbindEventCallback` has been removed as of V10.**


:new: in V8
You can simply subscribe to **every** event at once of Phpfastcache.

```php
<?php
use Phpfastcache\EventManager;

EventManager::getInstance()->addGlobalListener(static function (\Phpfastcache\Event\Event\EventInterface $event) {
    echo sprintf('Triggered event %s', $event::getName());
});
```

This is an exhaustive list, and it will be updated as soon as new events will be added to the Core.

:warning: Changed in V10:

1. Method `onEveryEvent` is DEPRECATED and changed its name. It is now called `addGlobalListener`.
2. Method `unbindAllEventCallbacks` has been renamed to `unbindAllListeners`.
3. Methods `onXxxxxxXxxxx` are now DEPRECATED. Use method `addListener()` instead.

## List of active events:
### ItemPool Events
- addListener(EventsInterface::CACHE_GET_ITEM, *Callable* **$callback**)
    - **Callback arguments**
      - *\Phpfastcache\Event\Event\CacheGetItemEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
       - Allow you to manipulate an item just before it gets returned by the getItem() method.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::getItem()*
        - *ExtendedCacheItemPoolInterface::getItems()*
        - *ExtendedCacheItemPoolInterface::getItemsByTag()*
        - *ExtendedCacheItemPoolInterface::getItemsAsJsonString()*

- addListener(EventsInterface::CACHE_GET_ITEMS, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\Event\CacheGetItemsEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate a set of items just before it gets returned by the getItems() method.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::getItem()*
        - *ExtendedCacheItemPoolInterface::getItems()*
        - *ExtendedCacheItemPoolInterface::getItemsByTag()*
        - *ExtendedCacheItemPoolInterface::getItemsAsJsonString()*

- addListener(EventsInterface::CACHE_GET_ALL_ITEMS, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\Event\CacheGetAllItemsEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate a set of cache keys just before they get fetched from the backend.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::getItem()*
        - *ExtendedCacheItemPoolInterface::getItems()*
        - *ExtendedCacheItemPoolInterface::getItemsByTag()*
        - *ExtendedCacheItemPoolInterface::getItemsAsJsonString()*

- addListener(EventsInterface::CACHE_DELETE_ITEM, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\Event\CacheDeleteItemEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate an item after being deleted (this event is not fired if `deleteItems()` is called). :exclamation: **Caution** The provided item is in pool-detached state.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::deleteItem()*
        - *ExtendedCacheItemPoolInterface::deleteItems()*
        - *ExtendedCacheItemPoolInterface::getItem()*
        - *ExtendedCacheItemPoolInterface::getItems()*
        - *ExtendedCacheItemPoolInterface::getItemsByTag()*
        - *ExtendedCacheItemPoolInterface::getItemsAsJsonString()*

- addListener(EventsInterface::CACHE_DELETE_ITEMS, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\Event\CacheDeleteItemsEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate multiple items after being deleted. :exclamation: **Caution** The provided item is in pool detached-state.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::deleteItem()*
        - *ExtendedCacheItemPoolInterface::deleteItems()*
        - *ExtendedCacheItemPoolInterface::getItem()*
        - *ExtendedCacheItemPoolInterface::getItems()*
        - *ExtendedCacheItemPoolInterface::getItemsByTag()*
        - *ExtendedCacheItemPoolInterface::getItemsAsJsonString()*

- addListener(EventsInterface::CACHE_SAVE_ITEM, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\Event\CacheSaveItemEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate an item just before it gets saved by the driver.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::commit()*
        - *ExtendedCacheItemPoolInterface::save()*

- addListener(EventsInterface::CACHE_SAVE_MULTIPLE_ITEMS, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\EventCacheSaveMultipleItemsEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate an array of items before they get saved by the driver.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::commit()*
        - *ExtendedCacheItemPoolInterface::save()*
        - *ExtendedCacheItemPoolInterface::saveMultiple()*

- addListener(EventsInterface::CACHE_SAVE_DEFERRED_ITEM, (*Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheSaveDeferredItemEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate an item just before it gets pre-saved by the driver.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::saveDeferred()*

- addListener(EventsInterface::CACHE_COMMIT_ITEM, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheCommitItemEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate and/or alter a set of items just before they gets pre-saved by the driver.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::commit()*

- addListener(EventsInterface::CACHE_CLEAR_ITEMS, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheClearItemsEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate a set of item just before they gets cleared by the driver.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::clear()*
        - *ExtendedCacheItemPoolInterface::clean()*

- addListener(EventsInterface::CACHE_WRITE_FILE_ON_DISK, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheWriteFileOnDiskEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to get notified when a file is written on disk.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::writefile()*

- addListener(EventsInterface::CACHE_GET_ITEM_IN_SLAM_BATCH, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheGetItemInSlamBatchEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to get notified each time a batch loop is looping
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::deleteItem()*
        - *ExtendedCacheItemPoolInterface::getItem()*
        - *ExtendedCacheItemPoolInterface::getItems()*
        - *ExtendedCacheItemPoolInterface::getItemsByTag()*
        - *ExtendedCacheItemPoolInterface::getItemsAsJsonString()*

- addListener(EventsInterface::CACHE_DRIVER_CHECKED, **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheDriverCheckedEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to bind an event when the driver prerequisites has passed but before it the `driverConnect()` is called.
    - **Risky Circular Methods**
        - *(none)*
- addListener(EventsInterface::CACHE_DRIVER_CONNECTED, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheDriverConnectedEvent* **$event**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to bind an event when the driver backend has been successfully instantiated and connected/authenticated (where applicable).
    - **Risky Circular Methods**
        - *(none)*
### ItemPool Events (Cluster) 
- addListener(EventsInterface::CACHE_GET_ITEM_IN_SLAM_BATCH, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheReplicationSlaveFallbackEvent* **$event**
    - **Scope**
        - Cluster pool
    - **Description**
        - Allow you to get notified when a Master/Slave cluster switches on slave
    - **Risky Circular Methods**
        - N/A

- addListener(EventsInterface::CACHE_REPLICATION_RANDOM_POOL_CHOSEN, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheReplicationRandomPoolChosenEvent* **$event**
    - **Scope**
        - Cluster pool
    - **Description**
        - Allow you to get notified when a Random Replication cluster choose a cluster
    - **Risky Circular Methods**
        - N/A

- addListener(EventsInterface::CACHE_CLUSTER_BUILT, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheClusterBuiltEvent* **$event**
    - **Scope**
        - Cluster aggregator
    - **Description**
        - Allow you to get notified when a cluster is being built
    - **Risky Circular Methods**
        - *$clusterAggregator::getCluster()*
### Item Events
- addListener(EventsInterface::CACHE_ITEM_SET, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheItemSetEvent* **$event**
    - **Scope**
        - Item
    - **Description**
        - Allow you to read (and rewrite) the value set to an item.
    - **Risky Circular Methods**
        - *ExtendedCacheItemInterface::get()*

- addListener(EventsInterface::CACHE_ITEM_EXPIRE_AT, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheItemExpireAtEvent* **$event**
    - **Scope**
        - Item
    - **Description**
        - Allow you to get/set the expiration date of an item.
    - **Risky Circular Methods**
        - *ExtendedCacheItemInterface::expiresAt()*

- addListener(EventsInterface::CACHE_ITEM_EXPIRE_AFTER, *Callable* **$callback**)
    - **Callback arguments**
        - *\Phpfastcache\Event\CacheItemExpireAfterEvent* **$event**
    - **Scope**
        - Item
    - **Description**
        - Allow you to get the "expire after" time of an item. If `$time` is a DateInterval you also set it.
    - **Risky Circular Methods**
        - *ExtendedCacheItemInterface::expiresAt()*

### Driver-specific Events (as of V9)
#### Arangodb
See [Arangodb extension event documentation](https://github.com/PHPSocialNetwork/arangodb-extension#events).

#### Couchdb (v9.2)
See [Couchdb extension event documentation](https://github.com/PHPSocialNetwork/couchdb-extension#events).

#### Dynamodb
See [Dynamodb extension event documentation](https://github.com/PHPSocialNetwork/dynamodb-extension#events).

#### Solr
See [Solr extension event documentation](https://github.com/PHPSocialNetwork/solr-extension#events).
