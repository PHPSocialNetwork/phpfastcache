Because the V7 is **absolutely** not backward compatible with the V6, here's a guide to help you to migrate your code:

### :warning: Phpfastcache global namespace  :warning:
As of the V7 and to comply with modern standards the Phpfastcache namespace has changed :warning:

#### :clock1: Then:
We used to declare this namespace: `namespace phpFastCache;` or `use phpFastCache\...;` 

#### :alarm_clock: Now:
The time has changed, you now have to use: `namespace Phpfastcache;` or `use Phpfastcache\...;` 
For any useful purpose, the `src/` directory has been moved along to `lib/`.

### :warning: Phpfastcache "phpFastCache*" classes  :warning:
As of the V7 and to comply with modern standards the "phpFastCache*" classes have been **C**apitalized :warning:

#### :clock1: Then:
We used to declare this kind of class: `class phpFastCacheAbstractProxy`

#### :alarm_clock: Now:
The time has changed, you now have to use: `class PhpfastcacheAbstractProxy` 
For any useful purpose, all the PhpFastCache exceptions has been **C**apitalized.
Class `Phpfastcache\Exceptions\phpFastCache[...]Exception` have been renamed to `Phpfastcache\Exceptions\Phpfastcache[...]Exception`

### Deprecations
- `Phpfastcache\CacheManager::getStaticAllDrivers()` replaced by `Phpfastcache\CacheManager::getDriverList()`
- `Phpfastcache\CacheManager::getStaticSystemDrivers()` replaced by `Phpfastcache\CacheManager::getDriverList()`
- Configuration option `ignoreSymfonyNotice` will not be replaced since that the related Symfony notice in `Phpfastcache\CacheManager::getInstance()` has been removed

### Configuration
- Added configuration option `fallbackConfig` for a better fallback configuration

### Return type & Scalar type declarations 
:anger: :exclamation: The V7 will make use of new php's return type & scalars type declarations features. 
This means that you will now have to be very careful about the data types that you are sending to the phpFastCache API.
Also PhpFastCache will make use of **strict types** provided by php7 by declaring `declare(strict_types=1);` in the top of each source file.
This ensure you a full compatibility and respect of php types.

### ActOnAll Helper

#### :clock1: Then:
The ActOnAll helper used to return an array of returns only for getters and a strict boolean of CRUD operations:
(
  Files::DeleteItem(): false + 
  Memcache::DeleteItem(): true + 
  Redis::DeleteItem(): true
) => Returning a strict FALSE

#### :alarm_clock: Now:
Whatever you call will now result in an array of returns giving you the possibility to know which driver is returning an unexpected response via the ActOnAll helper.
Also the ActOnAll helper does no longer implements `ExtendedCacheItemPoolInterface` due to the type hint implementation.

### Cache Manager: Instance ID
:new: As of the V7 you can now (optionally) specify an instance ID using the CacheManager:

```php
CacheManager::getInstance($defaultDriver, $options, $instanceId);
```
Please note that `$instanceId` must be a valid __STRING__.

There's also a variant that throw a `phpFastCacheInstanceNotFoundException` if the instance is not found.
```php
CacheManager::getInstanceById($instanceId);
```

### Configuration option names

#### :clock1: Then:
Some configuration names were still using "snake case" format:
- default_chmod
- compress_data
- sasl_user
- ...

#### :alarm_clock: Now:
These configuration names are now "Camelized":
- defaultChmod
- compressData
- saslUser
- ...

As of the V7 there's no "snake case" names left.

### Configuration option type

#### :clock1: Then:
The configuration data used to be a primitive array passed through a `CacheManager` method.

#### :alarm_clock: Now:
The will now accept only a `\Phpfastcache\Config\ConfigurationOption` object.
There's a short alias available for less verbose configuration: `\Phpfastcache\Config\Config`
This object will contain fluent setters allowing you to make use of chained setters.
Despite the fact that this is a bad practice, this object which implements ArrayAccess interface 
will allow you to treat the object as an array for primitive variable accesses:
```php
use Phpfastcache\Config\Config;

$configArray = [];
$config = new Config();
$config['path'] = '/an/absolute/path';
// The line above is doing exact same job as the line below:
$config->setPath('/an/absolute/path');
$cacheInstace = CacheManager::getInstance('Files', $config);
// This also works well:
$cacheInstace = CacheManager::getInstance('Files', new Config([
  'path' => '/an/absolute/path']
));
```
#### For non-global option you MUST use the specific Config object provided for each drivers 
```php
// The recommended way is to use an alias to not confuse yourself 
use Phpfastcache\Drivers\Files\Config as FilesConfig;

$config = new FilesConfig();
$config->setPath('/an/absolute/path');
$config->setSecureFileManipulation(true);
$cacheInstace = CacheManager::getInstance('Files', $config);

// This also works well:
$cacheInstace = CacheManager::getInstance('Files', new FilesConfig([
  'path' => '/an/absolute/path']
));
```

However the ArrayAccess interface has been implemented to make the migration easier, 
so we might reconsider it's usefulness in the future.
Then, we recommend you to use the standard object syntax to ensure you a better compatibility.
Please note that the `CacheManager` will still accepts a primitive array but will raise a
E_USER_DEPRECATED error if you do so.

### Cache manager default configuration

#### :clock1: Then:
The previous deprecated `CacheManager::setup()` method was kept until V6.
It has been definitely removed as of the V7.

#### :alarm_clock: Now:
Use the recommended `CacheManager::setDefaultConfig()` method instead

### Invalid configuration keys/values

#### :clock1: Then:
PhpFastCache ignored invalid configuration Key/value.

#### :alarm_clock: Now:
PhpFastCache will now throws a `phpFastCacheInvalidConfigurationException` if you
attempt to make use of an invalid configuration Key/value.

### API changelog

#### :clock1: Then:
The API changelog used to be hardcoded as a HEREDOC in the `Api::getChangelog()` method. 

#### :alarm_clock: Now:
The API changelog format has been moved to a MarDown file (.md)
If you were using `Api::getChangelog()` you may need to check that your code is still working as expected.
The method still returns a valid string but its format has changed slightly.

### Couchbase PHP SDK
The Couchbase driver has been updated to works with PHP SDK 2.4+ (Couchbase Server 5+).
Therefore it way requires a Couchbase Server updated combined with an SDK update.
 
### Constants autoload-related
As of the V7, all the constant starting by _PFC\_*_ were moved in     to the `Phpfastcache\Autoload` namespace\ 
to not pollute php's root namespace.

### Autoload mechanism
Although we highly recommend you to make use of composer benefits, it's however still possible\
to use our own standalone autoloader. It is now located in `lib/autoload`

