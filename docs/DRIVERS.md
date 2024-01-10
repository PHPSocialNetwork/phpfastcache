### Drivers descriptions
* Apc **(REMOVED in v8)**
  * The Alternative Php Cache (APC) driver. A memory cache for regular performances.
* Apcu
  * The Alternative Php User Cache (APCU) driver. A memory cache for regular performances.
* Arangodb **(Added in v9)**
  * A very high-performance NoSQL driver using a key-value pair system.
  * :new: Is now a composer extension separated from the Phpfastcache core as of v9.2: `phpfastcache/arangodb-extension`
* Cassandra
  * A very high-performance NoSQL driver using a key-value pair system. Please note that the Driver rely on php's Datastax extension: https://github.com/datastax/php-driver
  * As of PHP an unofficial fork has been launched due to Datastax lack of maintenance: https://github.com/he4rt/scylladb-php-driver
* Cookie **(REMOVED in v9)**
  * A cookie driver to store non-sensitive scalar (only) data. Limited storage up to 4Ko.
* Couchbase **(REMOVED in v9)**
  * A very high-performance NoSQL driver using a key-value pair system, replaced by Couchbasev3 as of v8.0.8.
* Couchbasev3 **(Added in v8.0.8)**
  * Same as Couchbase but for Couchbase PHP-SDK 3.0 support.
  * Will be deprecated as of v10
* Couchbasev4 **(Added in v9.2.0)**
  * Couchbase PHP-SDK 4.x support. 
  * :new: It is now a [separated extension](https://github.com/PHPSocialNetwork/couchbasev4-extension) which is no longer part of the Phpfastcache's core.
* Couchdb
  * A very high-performance NoSQL driver using a key-value pair system.
  * :new: Is now a composer extension separated from the Phpfastcache core as of v9.2: `phpfastcache/couchdb-extension`
* Devfalse **(REMOVED in v9)**
   * A development driver that return false for everything except driverCheck().
* Devnull
   * A development driver that return null for driverRead() and driverIsHit() (get actions) and true for other action such as driverDelete, DriverClear() etc.
* Devtrue **(REMOVED in v9)**
   * A development driver that return true for everything including driverCheck().
* Devrandom **(Added in v8.0.8)**
  * A development driver with configurable factor chance and data length.
* Dynamodb **(Added in v9)**
  * An AWS cloud NoSQL driver using a key-value pair system. Be careful when flushing the table as it will delete and recreate the table due to a Dynamodb limitation.
  * :new: Is now a composer extension separated from the Phpfastcache core as of v9.2: `phpfastcache/dynamodb-extension`
* Files
  * A file driver that use serialization for storing data for regular performances. A _$path_ config must be specified, else the system temporary directory will be used.
* Firestore **(Added in v9)**
  * A GCP cloud NoSQL driver using a key-value pair system. Collections are created automatically on-the-fly.
  * :new: Is now a composer extension separated from the Phpfastcache core as of v9.2: `phpfastcache/firestore-extension` 
* Leveldb
  * A NoSQL driver using a key-value pair system. A _$path_ config must be specified, else the system temporary directory will be used.
* Memcache
  * The Memcache driver. A memory cache for regular performances. Do not cross this driver with Memcached driver.
* Memcached
  * The Memcached driver. A memory cache for regular performances. Do not cross this driver with Memcache driver.
* Memstatic
  * The Memstatic driver is a memory static driver that expires when the script execution ends.
* Mongodb
  * A very high-performance NoSQL driver using a key-value pair system.
  * :new: Is now a composer extension separated from the Phpfastcache core as of v9.2: `phpfastcache/mongodb-extension`
* Predis
  * A high-performance memory driver using a in-memory data structure storage. Less efficient than Redis driver as it is an embedded library.
* Ravendb **(Added in v9.2)**
  * A Ravendb driver that use the `ravendb/ravendb-php-client` client for good performances.
  * :new: It is a composer extension separated from the Phpfastcache core as of v9.2: `phpfastcache/ravendb-extension`
* Redis/Rediscluster
  * A very high-performance memory driver using a in-memory data structure storage. More efficient than Predis driver as it is an compiled library.
  * RedisCluster use the RedisCluster class with a different driver name but behave slightly differently than Redis driver.
* Riak **(REMOVED in v8.0.6)**
  * A very high-performance NoSQL driver using a key-value pair system.
* Solr **(Added in v9.1)**
  * A Solr driver that use Solarium as PHP client for good performances.
  * :new: It is now a composer extension separated from the Phpfastcache core as of v9.2: `phpfastcache/solr-extension`
* Sqlite
  * A Sqlite driver that use serialization for storing data for regular performances. A _$path_ config must be specified, else the system temporary directory will be used.
* Ssdb
  * A very high-performance NoSQL driver using a key-value pair system.
* Wincache
  * The Wincache driver. A memory cache for regular performances on Windows platforms.
  * **Will be removed in v10** due to the lack of updates to PHP8 [as officially stated by PHP](https://www.php.net/manual/en/install.windows.recommended.php).
* Xcache **(REMOVED in v8)**
  * The Xcache driver. A memory cache for regular performances.
* Zend Disk Cache ( * Requires ZendServer Version 4 or higher * )
  * The Zend Data Cache is a by ZendServer supported file cache. The cache is for regular performance.
* Zend Memory Cache ( * Requires ZendServer Version 4 or higher * )
  * The Zend Memory Cache is a by ZendServer supported memory cache. The cache is for high-performance applications.
