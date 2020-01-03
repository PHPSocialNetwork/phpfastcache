## 8.0.0-dev
##### 17 december 2019
- Removed "Auto" driver in `CacheManager::getInstance()` you will now be mandatory to specify the driver to use.
- Removed deprecated feature `CacheManager::setNamespacePath()`  (replaced by cache manager "override" or "custom driver" features)
- Upgraded minimum php version support: `7.3+`
- Upgraded Phpfastcache API from `2.0.4` to `3.0.0`, be careful, there some minor Breaking Changes (BC).
- Implemented aggregated cluster support (See the Readme.MD)
- Removed Xcache support which is now [completely abandoned](https://xcache.lighttpd.net/) (latest update: 2014)
- Removed Apc (**but not APCu**) support which is now [completely abandoned](https://pecl.php.net/package/APC) (latest update: 2012)
- Removed `CacheManager::getStaticSystemDrivers()` (use `CacheManager::getDriverList()` instead)
- Added (required) cookie driver option `awareOfUntrustableData` to enforce developer awareness of non-reliable data storage
- The "ActOnAll Helper" have been removed in profit of aggregated cluster support
- Implemented #713 // Reworked "tags" feature by adding 3 strategies: `TAG_STRATEGY_ONE`, `TAG_STRATEGY_ALL`, `TAG_STRATEGY_ONLY`
- Removed *global static* properties `CacheManager::$ReadHits`, `CacheManager::$WriteHits` replaced by`\Phpfastcache\Entities\DriverIO` callable in`\Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface::getIO`