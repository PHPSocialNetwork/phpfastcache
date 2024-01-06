Because the V10 is **relatively** not backward compatible with the V9, here's a guide to help you to migrate your code:

### :warning: Minimum php version increased to 8.2+
As of the V9 the mandatory php version has been increased to 8.2+.
Once released, the php versions 8.3, 8.4 will be unit-tested 

### Added Microsoft Azure Cosmos DB driver
Extension Cosmosdb has been added has an extension: `phpfastcache/cosmosdb-extension`.

### Embedded document-oriented drivers have been moved from the core to their own extensions
- Driver `Arangodb` have been **removed**, use `phpfastcache/arangodb-extension` (via composer) instead.
- Driver `Couchdb` have been **removed**, use `phpfastcache/couchdb-extension` (via composer) instead.
- Driver `Cassandra` have been **removed**, use `phpfastcache/cassandra-extension` (via composer) instead.
- Driver `Dynamodb` have been **removed**, use `phpfastcache/dynamodb-extension` (via composer) instead.
- Driver `Firestore` have been **removed**, use `phpfastcache/firestore-extension` (via composer) instead.
- Driver `Mongodb` have been **removed**, use `phpfastcache/mongodb-extension` (via composer) instead.
- Driver `Solr` have been **removed**, use `phpfastcache/solr-extension` (via composer) instead.

However, driver `Couchbasev3` has been kept in the core for compatibility reasons but has been deprecated and will be removed as of v11.\
Use `phpfastcache/couchbasev4-extension` to upgrade your code to the latest version of Couchbase.\
Common drivers such as `Files`, `Redis`, `Ssdb`, `Sqlite` will stay in the core.

### `Memstatic` driver renamed to `Memory`
Previously deprecated in v9.2, the driver `Memstatic` has been removed, use `Memory` instead (which is completely identical, only the name changed).

### `Wincache` driver removed
Previously deprecated in v9.2, the driver has been removed with no replacement due to the lack of updates to PHP 8 [as officially stated by PHP](https://www.php.net/manual/en/install.windows.recommended.php).

### Class `\Phpfastcache\Config\Config` removed
Use `\Phpfastcache\Config\ConfigurationOption` instead.

### Class `\Phpfastcache\Helper\CacheConditionalHelper` removed
Use `\Phpfastcache\CacheContract` instead.

### Configurations deprecations and removals

#### Global:
Config methods `isPreventCacheSlams()/setPreventCacheSlams()/getCacheSlamsTimeout()/setCacheSlamsTimeout()` from `ConfigurationOption` have moved to `IOConfigurationOption` hence will be reserved to `Files`/`Leveldb`/`Sqlite` drivers.

#### Firestore:
Config `getCollection()/setCollection()` methods are renamed to `getCollectionName()/setCollectionName()`

------
More information in our comprehensive [changelog](./../../CHANGELOG.md).




