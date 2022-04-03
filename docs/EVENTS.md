:mega: As of the V6, Phpfastcache provides an event mechanism.
You can subscribe to an event by passing a Closure to an active event:

```php
use Phpfastcache\EventManager;

/**
* Bind the event callback
*/
EventManager::getInstance()->onCacheGetItem(function(ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item){
    $item->set('[HACKED BY EVENT] ' . $item->get());
});

```

An event callback can get unbind but you MUST provide a name to the callback previously:


```php
use Phpfastcache\EventManager;

/**
* Bind the event callback
*/
EventManager::getInstance()->onCacheGetItem(function(ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item){
    $item->set('[HACKED BY EVENT] ' . $item->get());
}, 'myCallbackName');


/**
* Unbind the event callback
*/
EventManager::getInstance()->unbindEventCallback('onCacheGetItem', 'myCallbackName');

```

:new: in V8

You can simply subscribe to **every** events at once of Phpfastcache.
```php
<?php
use Phpfastcache\EventManager;

EventManager::getInstance()->onEveryEvents(static function (string $eventName, ...$args) {
    echo sprintf("Triggered event '{$eventName}' with %d arguments provided", count($args));
}, 'debugCallback');
```

This is an exhaustive list, and it will be updated as soon as new events will be added to the Core.


:new: In V9

- Some callback parameter, that are __NOT__ objects, are passed by reference via the new `\Phpfastcache\Event\EventReferenceParameter` class.\
  This class is instantiated and passed to the callback with the original value passed **by reference** allowing you to either read or re-write its value.\
  If it's allowed by the event dispatcher the type can be changed or not.\
  If you try to while it's not allowed, you will get a `PhpfastcacheInvalidArgumentException` when trying to call `\Phpfastcache\Event\EventReferenceParameter::setParameterValue()`\
  Finally the class `\Phpfastcache\Event\EventReferenceParameter` is `invokable` and trying to do so will return you the parameter value.\
- A method named `unbindAllEventCallbacks(): bool` has been added to `EventManagerInterface` to allow you to unbind/clear all event from an event instance.
- Event callbacks will now receive the `eventName` as an extra _last_ callback parameter (except for `onEveryEvents` callbacks)
- Added `EventManagerInterface::on(array $eventNames, $callback)` method, to subscribe to multiple events in once with the same callback

## List of active events:
### ItemPool Events
- onCacheGetItem(*Callable* **$callback**)
    - **Callback arguments**
      - *ExtendedCacheItemPoolInterface* **$itemPool**
      - *ExtendedCacheItemInterface* **$item**
    - **Scope**
        - ItemPool
    - **Description**
       - Allow you to manipulate an item just before it gets returned by the getItem() method.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::getItem()*
        - *ExtendedCacheItemPoolInterface::getItems()*
        - *ExtendedCacheItemPoolInterface::getItemsByTag()*
        - *ExtendedCacheItemPoolInterface::getItemsAsJsonString()*

- onCacheDeleteItem(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *ExtendedCacheItemInterface* **$item**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate an item after being deleted. :exclamation: **Caution** The provided item is in pool detached-state.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::deleteItem()*
        - *ExtendedCacheItemPoolInterface::getItem()*
        - *ExtendedCacheItemPoolInterface::getItems()*
        - *ExtendedCacheItemPoolInterface::getItemsByTag()*
        - *ExtendedCacheItemPoolInterface::getItemsAsJsonString()*

- onCacheSaveItem(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *ExtendedCacheItemInterface* **$item**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate an item just before it gets saved by the driver.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::commit()*
        - *ExtendedCacheItemPoolInterface::save()*

- onCacheSaveMultipleItems(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *EventReferenceParameter($items)* **$items** _via EventReferenceParameter object_ **(type modification forbidden)**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate an array of items before they get saved by the driver.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::commit()*
        - *ExtendedCacheItemPoolInterface::save()*
        - *ExtendedCacheItemPoolInterface::saveMultiple()*

- onCacheSaveDeferredItem(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *ExtendedCacheItemInterface* **$item**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate an item just before it gets pre-saved by the driver.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::saveDeferred()*

- onCacheCommitItem(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *EventReferenceParameter($items)* **$items** _via EventReferenceParameter object_ **(type modification forbidden)**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate and/or alter a set of items just before they gets pre-saved by the driver.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::commit()*

- onCacheClearItem(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *ExtendedCacheItemInterface[]* **$items**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to manipulate a set of item just before they gets cleared by the driver.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::clear()*
        - *ExtendedCacheItemPoolInterface::clean()*

 - onCacheWriteFileOnDisk(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *string* **$file**
        - *bool* **$secureFileManipulation**
    - **Scope**
        - ItemPool
    - **Description**
        - Allow you to get notified when a file is written on disk.
    - **Risky Circular Methods**
        - *ExtendedCacheItemPoolInterface::writefile()*

- onCacheGetItemInSlamBatch(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *ItemBatch* **$driverData**
        - *int* **$cacheSlamsSpendSeconds**
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
### ItemPool Events (Cluster) 
- onCacheReplicationSlaveFallback(*Callable* **$callback**)
    - **Callback arguments**
        - *ClusterPoolInterface* **$self**
        - *string* **$caller**
    - **Scope**
        - Cluster pool
    - **Description**
        - Allow you to get notified when a Master/Slave cluster switches on slave
    - **Risky Circular Methods**
        - N/A

- onCacheReplicationRandomPoolChosen(*Callable* **$callback**)
    - **Callback arguments**
        - *ClusterPoolInterface* **$self**
        - *ExtendedCacheItemPoolInterface* **$randomPool**
    - **Scope**
        - Cluster pool
    - **Description**
        - Allow you to get notified when a Random Replication cluster choose a cluster
    - **Risky Circular Methods**
        - N/A

- onCacheClusterBuilt(*Callable* **$callback**)
    - **Callback arguments**
        - *AggregatorInterface* **$clusterAggregator**
        - *ClusterPoolInterface* **$cluster**
    - **Scope**
        - Cluster aggregator
    - **Description**
        - Allow you to get notified when a cluster is being built
    - **Risky Circular Methods**
        - *$clusterAggregator::getCluster()*
### Item Events
- onCacheItemSet(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemInterface* **$item**
        - *EventReferenceParameter($value)* **$value** _via EventReferenceParameter object_ **(type modification allowed)**
    - **Scope**
        - Item
    - **Description**
        - Allow you to read (and rewrite) the value set to an item.
    - **Risky Circular Methods**
        - *ExtendedCacheItemInterface::get()*

- onCacheItemExpireAt(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemInterface* **$item**
        - *\DateTimeInterface* **$expiration**
    - **Scope**
        - Item
    - **Description**
        - Allow you to get/set the expiration date of an item.
    - **Risky Circular Methods**
        - *ExtendedCacheItemInterface::expiresAt()*

- onCacheItemExpireAfter(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemInterface* **$item**
        - *int | \DateInterval* **$time**
    - **Scope**
        - Item
    - **Description**
        - Allow you to get the "expire after" time of an item. If `$time` is a DateInterval you also set it.
    - **Risky Circular Methods**
        - *ExtendedCacheItemInterface::expiresAt()*

### Driver-specific Events (as of V9)
#### Arangodb
- onArangodbConnection(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *EventReferenceParameter($connectionOptions)* **$connectionOptions** _via EventReferenceParameter object_ **(type modification forbidden)**
    - **Scope**
        - Arangodb Driver
    - **Description**
        - Allow you to alter the parameters built used to connect to Arangodb server
    - **Risky Circular Methods**: None

- onArangodbCollectionParams(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *EventReferenceParameter($params)* **$params** _via EventReferenceParameter object_ **(type modification forbidden)**
    - **Scope**
        - Arangodb Driver
    - **Description**
        - Allow you to alter the parameters built used to create the collection
    - **Risky Circular Methods**: None

#### Dynamodb
- onDynamodbCreateTable(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *EventReferenceParameter($params)* **$params** _via EventReferenceParameter object_ **(type modification forbidden)**
    - **Scope**
        - Dynamodb Driver
    - **Description**
        - Allow you to alter the parameters built used to create the table
    - **Risky Circular Methods**: None

#### Solr
- onSolrBuildEndpoint(*Callable* **$callback**)
    - **Callback arguments**
        - *ExtendedCacheItemPoolInterface* **$itemPool**
        - *EventReferenceParameter($params)* **$endpoints** _via EventReferenceParameter object_ **(type modification forbidden)**
    - **Scope**
        - Solr Driver
    - **Description**
        - Allow you to alter the endpoints built used to connect to Solr server
    - **Risky Circular Methods**: None
