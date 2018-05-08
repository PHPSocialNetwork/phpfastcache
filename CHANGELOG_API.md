## 2.0.4
- Added ExtendedCacheItemPoolInterface::getConfigClass() that returns the config class name

## 2.0.3
- Updated ExtendedCacheItemPoolInterface::setEventManager() first argument that now MUSt implement `\Phpfastcache\Event\EventInterface`
- Updated ExtendedCacheItemInterface::setEventManager() first argument that now MUSt implement `\Phpfastcache\Event\EventInterface`

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