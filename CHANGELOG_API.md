## 4.2.0
- Created method `\Phpfastcache\Cluster\AggregatablePoolInterface::isAggregatedBy(): ?ClusterPoolInterface` which will return the aggregator object for Cluster aggregators
- Created method `\Phpfastcache\Cluster\AggregatablePoolInterface::setAggregatedBy(ClusterPoolInterface $clusterPool): static` which will allow to set the aggregator object

## 4.1.0
- Created `\Phpfastcache\Event\EventInterface` which will be used for `Phpfastcache\Event\Event` and any `Phpfastcache\Drivers\xxxxxxx\Event` classes
- Extended `CacheItemPoolInterface::save()` with `ExtendedCacheItemPoolInterface::save()` for re-typing
- Method `ExtendedCacheItemPoolInterface::getConfig()` now returns `ConfigurationOptionInterface` instead of `ConfigurationOption`
- Method `ExtendedCacheItemPoolInterface::getDefaultConfig()` now returns `ConfigurationOptionInterface` instead of `ConfigurationOption`
- Method `EventManagerInterface::getInstance()` now returns `EventManagerInterface` instead of `static`

## 4.0.0
- **[BC Break]** Upgraded `psr/cache` dependency to `^3.0` which required `ExtendedCacheItemPoolInterface` and `ExtendedCacheItemInterface` updates
- **[BC Break]** Increased minimum PHP compatibility to `^8.0` which also required `TaggableCacheItemPoolInterface` and `TaggableCacheItemInterface` updates
- **[BC Break]** Updated `ExtendedCacheItemPoolInterface::saveMultiple(ExtendedCacheItemInterface...$items)` which no longer accept argument #0 to be itself an array of `ExtendedCacheItemInterface` objects
- **[BC Break]** Updated `ExtendedCacheItemPoolInterface::getConfigClass()` signature: it is now a **static** method
- Added `ExtendedCacheItemPoolInterface::getItemClass()`
- Added `ExtendedCacheItemInterface::hasTag(string $tag)` to test if a cache item is tagged with the provided tag
- Added `ExtendedCacheItemInterface::hasTag(string $tag)` to test if a cache item is tagged with the provided tag
- Added `ExtendedCacheItemInterface::cloneInto(ExtendedCacheItemInterface $itemTarget, ?ExtendedCacheItemPoolInterface $itemPoolTarget = null)` to clone a cache item into another with an optional pool object
- Referenced `TaggableCacheItemPoolInterface::TAG_STRATEGY_*` constants to `TaggableCacheItemInterface::TAG_STRATEGY_*` for more code usability

## 3.0.0
- **[BC Break]** Removed `ExtendedCacheItemPoolInterface::appendItemsByTagsAll()` (replaced by strategy `TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL`)
- **[BC Break]** Removed `ExtendedCacheItemPoolInterface::decrementItemsByTagsAll()` (replaced by strategy `TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL`)
- **[BC Break]** Removed `ExtendedCacheItemPoolInterface::deleteItemsByTagsAll()` (replaced by strategy `TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL`)
- **[BC Break]** Removed `ExtendedCacheItemPoolInterface::getItemsByTagsAll()` (replaced by strategy `TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL`)
- **[BC Break]** Removed `ExtendedCacheItemPoolInterface::incrementItemsByTagsAll()` (replaced by strategy `TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL`)
- **[BC Break]** Removed `ExtendedCacheItemPoolInterface::prependItemsByTagsAll()` (replaced by strategy `TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL`)
- **[BC Break]** Removed deprecated method `ExtendedCacheItemPoolInterface::getConfigOption()` (Use getConfig()->getOptionName() instead)
- **[BC Break]** Removed deprecated method `ExtendedCacheItemPoolInterface::isUsableInAutoContext()` (Since "Auto" driver has been removed)
- Added strategy`TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE` usable in every `**byTags**` methods.
- Added strategy`TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL` usable in every `**byTags**` methods.
- Added strategy`TaggableCacheItemPoolInterface::TAG_STRATEGY_ONLY` usable in every `**byTags**` methods.

## 3.0.0-rc
- **[BC Break]** Moved `\Phpfastcache\Event\EventInterface` to`\Phpfastcache\Event\EventManagerInterface`
- Moved (by extends) ExtendedCacheItemPoolInterface::setEventManager() in `\Phpfastcache\Event\EventManagerDispatcherInterface:setEventManager()`
- Moved (by extends) ExtendedCacheItemInterface::doesItemBelongToThatDriverBackend() in `\Phpfastcache\Event\EventManagerDispatcherInterface::setEventManager()`
- Added `\Phpfastcache\Event\EventManagerDispatcherInterface`
- Added `ExtendedCacheItemInterface::doesItemBelongToThatDriverBackend()`
- Added `\Phpfastcache\Event\EventManagerInterface:onEveryEvents()`

## 2.0.4
- Added ExtendedCacheItemPoolInterface::getConfigClass() that returns the config class name

## 2.0.3
- Updated ExtendedCacheItemPoolInterface::setEventManager() first argument that now MUST implement `\Phpfastcache\Event\EventInterface`
- Updated ExtendedCacheItemInterface::setEventManager() first argument that now MUST implement `\Phpfastcache\Event\EventInterface`

## 2.0.2
- Added ExtendedCacheItemPoolInterface::isUsableInAutoContext() to check if the driver is allowed to be used in 'Auto' context.

## 2.0.1
- Implemented additional atomic methods:
- Added ExtendedCacheItemInterface::isNull() to test if the data is null or not despite the hit/miss status.
- Added ExtendedCacheItemInterface::isEmpty() to test if the data is empty or not despite the hit/miss status.
- Added ExtendedCacheItemInterface::getLength() get the data length if the data is a string, array, or objects that implement \Countable interface.

## 2.0.0
- Introduced BC breaks:
- Updated ExtendedCacheItemPoolInterface to be compliant with the new \$config object introduced in V7.
- ExtendedCacheItemPoolInterface::getConfig() no longer returns an array but a ConfigurationOption object
- ExtendedCacheItemPoolInterface::getDefaultConfig() no longer returns an array but a ConfigurationOption object
- Removed ExtendedCacheItemInterface::getUncommittedData() that is no longer used in the V7

## 1.3.0
- Implemented full PHP7 type hint support for ExtendedCacheItemPoolInterface and ExtendedCacheItemInterface
- Added instance ID getter (introduced in V7):
  - ExtendedCacheItemPoolInterface::getInstanceId()
- The method ExtendedCacheItemPoolInterface::getDefaultConfig() will now returns a \phpFastCache\Util\ArrayObject

## 1.2.5
- Implemented additional simple helper method to direct access to a config option:
  - ExtendedCacheItemPoolInterface::getConfigOption()

## 1.2.4
- Implemented additional simple helper method to provide basic information about the driver:
  - ExtendedCacheItemPoolInterface::getHelp()

## 1.2.3
- Implemented additional saving method form multiple items:
   ExtendedCacheItemPoolInterface::saveMultiple()

## 1.2.2
- Implemented additional tags methods such as:
  - ExtendedCacheItemPoolInterface::getItemsByTagsAll()
  - ExtendedCacheItemPoolInterface::incrementItemsByTagsAll()
  - ExtendedCacheItemPoolInterface::decrementItemsByTagsAll()
  - ExtendedCacheItemPoolInterface::deleteItemsByTagsAll()
  - ExtendedCacheItemPoolInterface::appendItemsByTagsAll()
  - ExtendedCacheItemPoolInterface::prependItemsByTagsAll()

## 1.2.1
- Implemented Event manager methods such as:
  - ExtendedCacheItemInterface::setEventManager()
  - ExtendedCacheItemPoolInterface::setEventManager()

## 1.2.0
- Implemented Item advanced time methods such as:
  - ExtendedCacheItemInterface::setExpirationDate() (Alias of CacheItemInterface::ExpireAt() for more code logic)
  - ExtendedCacheItemInterface::getCreationDate() * 
  - ExtendedCacheItemInterface::getModificationDate() *
  - ExtendedCacheItemInterface::setCreationDate(\DateTimeInterface) *
  - ExtendedCacheItemInterface::setModificationDate() *
    - \* Require configuration directive "itemDetailedDate" to be enabled

## 1.1.3
- Added an additional CacheItemInterface method:
  - ExtendedCacheItemInterface::getEncodedKey()

## 1.1.2
- Implemented [de|a]ttaching methods to improve memory management
  - ExtendedCacheItemPoolInterface::detachItem()
  - ExtendedCacheItemPoolInterface::detachAllItems()
  - ExtendedCacheItemPoolInterface::attachItem()
  - ExtendedCacheItemPoolInterface::isAttached()

## 1.1.1
- Implemented JsonSerializable interface to ExtendedCacheItemInterface

## 1.1.0
- Implemented JSON methods such as:
  - ExtendedCacheItemPoolInterface::getItemsAsJsonString()
  - ExtendedCacheItemPoolInterface::getItemsByTagsAsJsonString()
  - ExtendedCacheItemInterface::getDataAsJsonString()

## 1.0.0
- First initial version
