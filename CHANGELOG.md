## 8.0.0-dev
##### 17 december 2019
- Upgraded minimum php version support: `7.3+`
- Upgraded Phpfastcache API from `2.0.4` to `3.0.0`, be careful, there some minor Breaking Changes.
- Implemented aggregated cluster support (See the Readme.MD)
- Removed Xcache support which is now [completely abandoned](https://xcache.lighttpd.net/) (latest update: 2014)
- Removed Apc (**but not APCu**) support which is now [completely abandoned](https://pecl.php.net/package/APC) (latest update: 2012)
- Removed `CacheManager::getStaticSystemDrivers()` (use `CacheManager::getDriverList()` instead)
- Added (required) cookie driver option `awareOfUntrustableData` to enforce developer awareness of non-reliable data storage
- The "ActOnAll Helper" have been remove in profit of aggregated cluster support