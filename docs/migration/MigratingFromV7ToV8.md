Because the V8 is **relatively** not backward compatible with the V7, here's a guide to help you to migrate your code:

### :warning: Minimum php version increased to 7.3+
As of the V8 the mandatory minimum php version has been increased to 7.3+.
Once released, the php version 8.0 will be unit-tested 

### :warning: "Auto" driver removed
As of the V8  the "Auto" driver in `CacheManager::getInstance()` has been removed.\
You will now be mandatory to specify the driver to use.

#### :clock1: Then:
`CacheManager::getInstance('Auto')` or `CacheManager::getInstance()` expecting automatic driver chosen

#### :alarm_clock: Now:
Use `CacheManager::getInstance('Files')` or `CacheManager::getInstance('Redis')` or something else.

### Removal of "APC" driver
As of the V8  the "APC" has been removed. Use "APCu" instead.

#### :clock1: Then:
`CacheManager::getInstance('Apc')`

#### :alarm_clock: Now:
`CacheManager::getInstance('Apcu')`

### Removal of "Xcache" driver
As of the V8 the "Xcache" has been removed, no replacement have been made.\
It is [completely abandoned](https://xcache.lighttpd.net/) (latest update: 2014)
Use alternative memory cache such as Redis, Memcache, Ssdb, etc.

#### :clock1: Then:
`CacheManager::getInstance('Xcache')`

#### :alarm_clock: Now:
Find an alternative :)

### Phpfastcache API has been upgraded to 3.0.0
Check the [CHANGELOG_API.md](./../../CHANGELOG_API.md) to see the changes.

### Removal of static counters
As of the V8 the static I/O counter have been removed

#### :clock1: Then:
Calling `CacheManager::$ReadHits`, `CacheManager::$WriteHits`

#### :alarm_clock: Now:
Replaced by`\Phpfastcache\Entities\DriverIO` callable in`\Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface::getIO`

### Removal of deprecated method `CacheManager::setNamespacePath()` 
As of the V8 the deprecated method `CacheManager::setNamespacePath()` have been removed.

#### :clock1: Then:
Calling `CacheManager::setNamespacePath('some\custom\path')`

#### :alarm_clock: Now:
Replaced by cache manager "override" or "custom driver" features introduced in V7

### Removal of deprecated method  `CacheManager::getStaticSystemDrivers()` 
As of the V8 the deprecated method `CacheManager::getStaticSystemDrivers()` have been removed.

#### :clock1: Then:
Calling `CacheManager::getStaticSystemDrivers()` 

#### :alarm_clock: Now:
Replaced by `CacheManager::getDriverList()`

### Removal of `ActOnAll` helper

#### :clock1: Then:
The helper `ActOnAll` used to be useful to act on all instance 

#### :alarm_clock: Now:
The "ActOnAll Helper" have been removed in profit of aggregated cluster support

### Removal of `fallback` feature

#### :clock1: Then:
The `fallback` features used to be useful when a backend failed to initialize

#### :alarm_clock: Now:
Use aggregated cluster Master/Slave instead

------
More infos in our comprehensive [changelog](./../../CHANGELOG.md).




