PhpFastCache has some options that you may want to know before using them, here's the list:

### File-based drivers options *
* **path** See the "**Host/Authenticating options**" section
* **default_chmod** _| int>octal (default: 0777)_ `[>=V4, <V7]` This option will define the chmod used to write driver cache files.
* **defaultChmod** _| int>octal (default: 0777)_ `[>=V7]` New configuration name of `default_chmod` as of V7
* **securityKey** _| string (default: 'auto')_ `[>=V4]` A security key that define the subdirectory name. 'auto' value will be the HTTP_HOST value.
* **htaccess** _| bool (default: true)_ `[>=V4]` Option designed to (dis)allow the auto-generation of .htaccess.
* **autoTmpFallback** _| bool (default: false)_ `[>=V6]`Option designed to automatically attempt to fallback to temporary directory if the cache fails to write on the specified directory
* **secureFileManipulation** _| bool (default: false)_ `[>=V6]` This option enforces a strict I/O manipulation policy by adding more integrity checks. This option may slow down the write operations, therefore you must use it with caution. In case of failure an **phpFastCacheIOException** exception will be thrown. Currently only supported by _Files_ driver.
* **cacheFileExtension** _| string (default: 'txt')_ `[>=V6.0.2]` This allows you to setup a custom (but safe) cache file extension.

\* Drivers like _Files_, _Sqlite_, _Leveldb_, etc.

### Global options
* **fallback** _| string|bool_  (default: false)`[>=V4.2]` A driver name used in case the main driver stopped working. E.g. a missing php extension.
* **fallbackConfig** _| ConfigurationOption|null_  (default: null)`[>=V7]` A dedicated `ConfigurationOption` object for the `fallback` driver, if needed.
* **compress_data** _| bool (default: false)_ `[>=V4.3]` Compress stored data, if the backend supports it. Currently only supported by _Memcache(d)_ driver.
* **limited_memory_each_object** _| int (default: 4096)_ `[>=V4.2]` Maximum size (bytes) of object stored in memory. Currently only supported by _Cookie_ driver.
* **defaultTtl** _| int (default: 900)_ `[>=V5]` This option define a default ttl (time-to-live, in seconds) for item that has no specified expiration date/ttl.
* **itemDetailedDate** _| bool (default: false)_ `[>=V6]` This option will define if the Items will make use of detailed dates such as Creation/modification date. Trying to access to these date without being explicitly enabled will throw a `LogicException`
* **defaultKeyHashFunction** _| string (default: 'md5')_ `[>=V6]` This option will allow you to define a custom hash function for the `$item->getEncodedKey()` method. Callable objects are not allowed, but static method such as `\Absolute\Namespace\ToStatic::method` are allowed.
* **defaultFileNameHashFunction** _| string (default: 'md5')_ `[>=V7]` This option will allow you to define a custom hash function for every I/O method that ends up to write an hashed filename on the disk.
* **preventCacheSlams** _| bool (default: false)_ `[>=V6]` This option will allow you to prevent cache slams when making use of heavy cache items
* **cacheSlamsTimeout** _| int (default: 15)_ `[>=V6]` This option defines the cache slams timeout in seconds
* **useStaticItemCaching** _| bool(default: true)_ `[>=V8.0.3]` This option will allow you to disable the internal static storage of cache items. Can be used for cron script that loop indefinitely on cache items to avoid calling `detachAllItems()`/`detachItem($item)` from the cache pool.

### Host/Authenticating options *
* **host** _| string (default: null)_ The hostname
* **path** _| string (default: null|string[temp. dir.])_ `[>=V4], [>=V6.1]` The absolute path where the written cache files belong to (system temp directory by default). **As of the V6.1** this option is also used to define (P)redis and Memcache(d) UNIX socket
* **port** _| int (default: null)_ The port
* **username** _| string (default: null)_ The username
* **password** _| string (default: null)_ The password
* **timeout** _| int (default: null)_ The timeout (in seconds)
* **servers** _| array (default: null)_ Array of servers. Exclusive to Memcache(d)

### Redis/Predis specific options
* **redisClient** _| \Redis (default: null)_ `[>=V7]` An optional Redis Client created outside Phpfastcache's scope. This option overrides every _Host/Authenticating_ options
* **predisClient** _| \Predis\Client (default: null)_ `[>=V7]` An optional Predis Client created outside Phpfastcache's scope. This option overrides every _Host/Authenticating_ options

These options differs depending the driver that you are using, see **/Examples** folder for more information about these options.

\* Drivers like _CouchBase_, _MongoDb_, _(P)redis_, _Ssdb_, etc.

