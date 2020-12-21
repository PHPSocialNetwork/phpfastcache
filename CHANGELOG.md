## 8.0.4
#### _"Confined Xmas"_
##### 21 december 2020
- __Core__
  - Added full PHP8 compatibility
- __Tests__
  - Added PHP8 test suite
- __Drivers__
  - Fixed #774 // Redis config cannot accept null values as the given example (@GeoSot)
- __Misc__
  - Small optimizations

## 8.0.3
#### _"Reconfined"_
##### 23 november 2020
- __Core__
    - Fixed #768 // Psalm issue with the 3rd parameter of Psr16Adapter::set has to be null (@geolim4)
    - Fixed #771 // DeleteItemsByTags was ignoring strategy #772  (@GeoSot)
    - Fixed inconsistent behavior of "defaultKeyHashFunction" and "defaultFileNameHashFunction" + added tests for them (@geolim4)
    - Implemented #754 // Added deactivatable static item caching support (@geolim4)
- __Tests__
    - Fixed test title for "DisabledStaticItemCaching.test.php"
- __Drivers__
    - Fixed #759 // Memcached Bytes Replaced with Version (@geolim4)

## 8.0.2
#### _"End of first wave"_
##### 28 august 2020
- __Drivers__
    - Fixed #744 // Added Memcached::OPT_PREFIX_KEY option support (@geolim4)

## 8.0.1
#### _"Still confined"_
##### 24 april 2020
- __Drivers__
    - Fixed #731 // Removing path check in Redis driver before auth. (@gillytech)
- __Misc__
    - Fixed some doc typo (@geolim4)

## 8.0.0
#### _"The quarantine"_
##### 01 january 2020
- Removed "Auto" driver in `CacheManager::getInstance()` you will now be mandatory to specify the driver to use.
- Removed deprecated feature `CacheManager::setNamespacePath()`  (replaced by cache manager "override" or "custom driver" features)
- Upgraded minimum php version support: `7.3+`
- Upgraded Phpfastcache API from `2.0.4` to `3.0.0`, be careful, there some minor Breaking Changes (BC).
- Implemented aggregated cluster support (See the Readme.MD)
- Removed Xcache support which is now [completely abandoned](https://xcache.lighttpd.net/) (latest update: 2014)
- Removed Apc (**but not APCu**) support which is now [completely abandoned](https://pecl.php.net/package/APC) (latest update: 2012)
- Removed `CacheManager::getStaticSystemDrivers()` (use `CacheManager::getDriverList()` instead)
- Added (required) cookie driver option `awareOfUntrustableData` to enforce developer awareness of non-reliable data storage
- Removed driver option `ignoreSymfonyNotice` and its getter/setter
- The "ActOnAll Helper" have been removed in profit of aggregated cluster support
- Implemented #713 // Reworked "tags" feature by adding 3 strategies: `TAG_STRATEGY_ONE`, `TAG_STRATEGY_ALL`, `TAG_STRATEGY_ONLY`
- Removed *global static* properties `CacheManager::$ReadHits`, `CacheManager::$WriteHits` replaced by`\Phpfastcache\Entities\DriverIO` callable in`\Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface::getIO`
- Removed deprecated method `ConfigurationOption::getOption()` use `getOptionName()` instead
- Removed deprecated config option `$ignoreSymfonyNotice`
- Removed "fallback" feature (use aggregated cluster Master/Slave instead)
- Enforced PSR-12 compliance
- Deprecated legacy autoload for removal in next major release
