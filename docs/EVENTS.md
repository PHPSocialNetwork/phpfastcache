### Events introduction

* onCacheGetItem
  * Allow you to manipulate an item just before it gets returned by the getItem() method.
 onCacheDeleteItem
  * Allow you to manipulate an item just before it gets returned by the getItem() method.
* onCacheSaveItem
  * Allow you to manipulate an item just before it gets saved by the driver.
* onCacheSaveDeferredItem
  * Allow you to manipulate an item just before it gets pre-saved by the driver.
* onCacheCommitItem
  * Allow you to manipulate a set of items just before they gets pre-saved by the driver.
* onCacheClearItem
  * Allow you to manipulate a set of item just before they gets cleared by the driver.
* onCacheItemSet
  * Allow you to get the value set to an item.
* onCacheItemExpireAt
  * Allow you to get/set the expiration date of an item.
* onCacheItemExpireAfter
  * Allow you to get the "expire after" time of an item. If `$time` is a DateInterval you also set it.
* onCacheWriteFileOnDisk
  * Allow you to get notified when a file is written on disk.
* onCacheGetItemInSlamBatch
  * Allow you to get notified each time a batch loop is looping

This is an exhaustive list and it will be updated as soon as new events will be added to the Core.
More details on the 
[WIKI](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%CB%96%5D-Introducing-to-events).