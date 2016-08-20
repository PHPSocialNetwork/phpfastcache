:mega: As of the V6, PhpFastCache provides an event mechanism.
You can subscribe to an event by passing a Closure to an active event:

```php
use phpFastCache\EventManager;

/**
* Bind the event callback
*/
EventManager::getInstance()->onCacheGetItem(function(ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item){
    $item->set('[HACKED BY EVENT] ' . $item->get());
});

```

An event callback can get unbind but you MUST provide a name to the callback previously:


```php
use phpFastCache\EventManager;

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


List of active events:

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
    - *ExtendedCacheItemInterface[]* **$items**
  - **Scope**
    - ItemPool
  - **Description**
    - Allow you to manipulate a set of items just before they gets pre-saved by the driver.
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

- onCacheItemSet(*Callable* **$callback**)
  - **Callback arguments**
    - *ExtendedCacheItemInterface* **$item**
    - *mixed* **$value**
  - **Scope**
    - Item
  - **Description**
    - Allow you to get the value set to an item.
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

This is an exhaustive list and it will be updated as soon as new events will be added to the Core. 