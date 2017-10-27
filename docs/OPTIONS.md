PhpFastCache has some options that you may want to know before using them, here's the list:

### File-based drivers options *
* **default_chmod** _| int>octal (default: 0777)_ `[>=V4]` This option will define the chmod used to write driver cache files. 
* **securityKey** _| string (default: 'auto')_ `[>=V4]` A security key that define the subdirectory name. 'auto' value will be the HTTP_HOST value.
* **path** _| string (default: Tmp directory)_ `[>=V4]` The absolute path where the written cache files belong to.
* **htaccess** _| bool  (default: true)_ `[>=V4]` Option designed to (dis)allow the auto-generation of .htaccess.
* **autoTmpFallback** _| bool  (default: false)_ `[>=V6]`Option designed to automatically attempt to fallback to temporary directory if the cache fails to write on the specified directory
* **secureFileManipulation** _| bool  (default: false)_ `[>=V6]` This option enforces a strict I/O manipulation policy by adding more integrity checks. This option may slow down the write operations, therefore you must use it with caution. In case of failure an **phpFastCacheIOException** exception will be thrown. Currently only supported by _Files_ driver.
* **cacheFileExtension** _| string (default: 'txt')_ `[>=V6.0.2]` This allows you to setup a custom (but safe) cache file extension.

\* Drivers like _Files_, _Sqlite_, _Leveldb_, etc.

### Global options
* **fallback** _| string|bool_  (default: false)`[>=V4.2]` A driver name used in case the main driver stopped working. E.g. a missing php extension.
* **compress_data** _| bool  (default: false)_ `[>=V4.3]` Compress stored data, if the backend supports it. Currently only supported by _Memcache(d)_ driver.
* **limited_memory_each_object** _| int (default: 4096)_ `[>=V4.2]` Maximum size (bytes) of object stored in memory. Currently only supported by _Cookie_ driver.
* **defaultTtl** _| int (default: 900)_ `[>=V5]` This option define a default ttl (time-to-live, in seconds) for item that has no specified expiration date/ttl.
* **itemDetailedDate** _| bool (default: false)_ `[>=V6]` This option will define if the Items will make use of detailed dates such as Creation/modification date. Trying to access to these date without being explicitly enabled will throw a `LogicException`
* **defaultKeyHashFunction** _| string (default: 'md5')_ `[>=V6]` This option will allow you to define a custom hash function for the `$item->getEncodedKey()` method. Callable objects are not allowed, but static method such as `\Absolute\Namespace\ToStatic::method` are allowed.
* **preventCacheSlams** _| bool (default: false)_ `[>=V6]` This option will allow you to prevent cache slams when making use of heavy cache items
* **cacheSlamsTimeout** _| int (default: 15)_ `[>=V6]` This option defines the cache slams timeout in seconds

### Host/Authenticating options *
* **host** _| string (default: not set)_ The host
* **port** _| int (default: not set)_ The port
* **username** _| string (default: not set)_ The username
* **password** _| string (default: not set)_ The password
* **timeout** _| int (default: not set)_ The timeout (in seconds)

These options differs depending the driver that you are using, see **/Examples** folder for more information about these options.

\* Drivers like _CouchBase_, _MongoDb_, _(P)redis_, _Ssdb_, etc.

