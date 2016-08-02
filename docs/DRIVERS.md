### Driver descriptions
* Apc
  * The Alternative Php Cache (APC) driver. A memory cache for regular performances.
* Apcu
  * The Alternative Php User Cache (APCU) driver. A memory cache for regular performances.
* Cookie
  * A cookie driver to store non-sensitive scalar (only) data. Limited storage up to 4Ko.
* Couchbase
  * A very high-performance NoSQL driver using a key-value pair system
* Devfalse
   * A development driver that return false for everything except driverCheck()
* Devnull
   * A development driver that return null for driverRead() and driverIsHit() (get actions) and true for other action such as driverDelete, DriverClear() etc.
* Devtrue
   * A development driver that return true for everything including driverCheck()
* Files
  * A file driver that use serialization for storing data for regular performances. A _$path_ config must be specified, else the system temporary directory will be used.
* Leveldb
  * A NoSQL driver using a key-value pair system. A _$path_ config must be specified, else the system temporary directory will be used.
* Memcache
  * The Memcache driver. A memory cache for regular performances. Do not cross this driver with Memcached driver
* Memcached
  * The Memcached driver. A memory cache for regular performances. Do not cross this driver with Memcache driver
* Mongodb
  * A very high-performance NoSQL driver using a key-value pair system
* Predis
  * A high-performance memory driver using a in-memory data structure storage. Less efficient than Redis driver as it is an embedded library
* Redis
  * A very high-performance memory driver using a in-memory data structure storage. More efficient than Predis driver as it is an compiled library
* Sqlite
  * A Sqlite driver that use serialization for storing data for regular performances. A _$path_ config must be specified, else the system temporary directory will be used.
* Ssdb
  * A very high-performance NoSQL driver using a key-value pair system
* Wincache
  * The Wincache driver. A memory cache for regular performances on Windows platforms.
* Xcache
  * The Xcache driver. A memory cache for regular performances.
* Zend Disk Cache ( * Requires ZendServer Version 4 or higher * )
  * The Zend Data Cache is a by ZendServer supported file cache. The cache is for regular performance.
* Zend Memory Cache ( * Requires ZendServer Version 4 or higher * )
  * The Zend Memory Cache is a by ZendServer supported memory cache. The cache is for high-performance applications.