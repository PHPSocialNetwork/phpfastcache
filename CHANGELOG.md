## 8.1.4
#### _"Masks fell, for good.."_
##### 12 february 2023
- __Core__
  - Fixed #893 // getItemsByTag() - empty after one item has expired

## 8.1.3
#### _"Don't throw the masks, yet."_
##### 25 may 2022
- __Core__
  - Fixed #860 // Cache item throw an error on save with DateTimeImmutable date objects
- __Drivers__
  - Fixed #866 // Deprecated Method Cassandra\ExecutionOptions starting of Cassandra 1.3

## 8.1.2
#### _"Free the masks"_
##### 04 march 2022
- __Drivers__
  - Fixed #853 // Configuration validation issue with Memcached socket (path)

## 8.1.1
#### _"Re-re-Vaccinated"_
##### 21 february 2022
- __Core__
  - Fixed #848 // Others PHP 8.1 compatibility bugs

## 8.1.0
#### _"Re-Vaccinated"_
##### 05 january 2022
- __Core__
  - Fixed #831 // Bug in the PSR-16 getMultiple method
- __Utils__
  - Fixed #846 // PHP 8.1 compatibility bug
- __Drivers__
  - Fixed #840 // Invalid type hint found for "htaccess", expected "string" got "boolean" for leveldb driver
- __Misc__
  - Updated some docs files (fixed typos)
- __Tests__
  - Migrate all Travis tests on bionic

## 8.0.8
#### _"Sanitary-passed"_
##### 18 august 2021
- __Core__
  - Fixed small date issue with tag items that stays longer than necessary active in backend
- __Drivers__
  - Improved Mongodb driver code
  - Improved Couchdb driver code
  - Improved Couchbase driver code (SDK 2 version)
  - Implemented #721 // Added Couchbase SDK 3 support (use `Couchbasev3` driver name)
- __Misc__
  - Increased test reliability by adding more code coverage in CRUD tests and by performing some updates on Travis CI

## 8.0.7
#### _"Vaccinated"_
##### 12 august 2021
- __Drivers__
  - Improved Couchdb driver code and tests
  - Dropped Riak support permanently (unmaintainable)
- __Docs__
  - Fixed vulnerability issue that cause exposed phpinfo() in some situations (@geolim4)
  
## 8.0.6
#### _"Re-deconfined"_
##### 07 july 2021
- __Helpers__
  - Allow $cacheItem to be retrieved by callback argument in CacheConditionalHelper (@geolim4)

## 8.0.5
#### _"Re-re-confined"_
##### 05 april 2021
- __Drivers__
  - Fixed #782 // Random warning in Files driver
  - Fixed #781 // bad type hint Riak driver
  - Fixed #788 // Redundant directory name for Sqlite

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
