## 9.1.2
##### 09 june 2022
- __API__
  - Upgraded Phpfastcache API to `4.2.0` ([see changes](CHANGELOG_API.md))
- __Core__
  - Rewrote some core code to improve code maintainability & readability following Scrutinizer and Phpstan recommendations
  - Fixed an issue with tags not properly reinitialized when a backend driver returns an expired cache item
- __Drivers__
  - Fixed #866 // Deprecated Method Cassandra\ExecutionOptions starting of Cassandra 1.3
- __Misc__
  - Increased PHPSTAN level to 6
  - Fixed multiple fails of Travis CI
  - Migrated Github issue templates from Markdown to YAML configurations

## 9.1.1
##### 15 april 2022
- __Core__
  - Fixed #860 // Cache item throw an error on reading with DateTimeImmutable date objects
  - Fixed an issue with tags not properly reinitialized when a backend driver returns an expired cache item
- __Drivers__
  - Fixed #862 // Multiple driver errors caused by invalid return type of `driverRead()` (reported by @ShockedPlot7560 and @aemla)

## 9.1.0
##### 04 april 2022
- __API__
  - Upgraded Phpfastcache API to `4.1.0` ([see changes](CHANGELOG_API.md))
- __Core__
  - Added `\Phpfastcache\Helper\UninstanciableObjectTrait` trait which will contains base locked constructor for any classes that are nor meant to be instanciated.
  - Deprecated `\Phpfastcache\Config\Config::class` 
  - Removed/reworked/improved dead/unreachable/redundant/obsolete code, thanks to `Phpstan`
- __Drivers__
  - **Added `Solr` driver support**
- __Events__
  - Added `\Phpfastcache\Event\EventInterface` for `\Phpfastcache\Event\Event` and subclasses below
  - Added `\Phpfastcache\Drivers\Arangodb\Event` for Arangodb events
  - Added `\Phpfastcache\Drivers\Dynamodb\Event` for Dynamodb events
  - Added `\Phpfastcache\Drivers\Solr\Event` for Solr events
  - Moved the following constant from `\Phpfastcache\Event\Event` to their respective drivers: `ARANGODB_CONNECTION`, `ARANGODB_COLLECTION_PARAMS`, `DYNAMODB_CREATE_TABLE`
- __Cluster__
  - Fixed #855 // ClusterReplication drivers are saving erroneous expiration date in low-level backends
- __Misc__
  - Full PSR-12 compliance is now enforced by PHPCS
  - Multiple typo fixes (@mbiebl)
  - Updated composer suggestions and CI builder dependencies

## 9.0.2
##### 04 march 2022
- __Core__
  - Updated CacheContract::__invoke() signature
  - Added new option to allow EventManager override + improved EventManager tests (EventManager::setInstance())
- __Drivers__
  - Fixed #853 // Configuration validation issue with Memcached socket (path)
- __Misc__
  - Fixed typo and some types hint

## 9.0.1
##### 14 november 2021
- __Core__
  - Added `\Phpfastcache\Event\Event` class for centralizing event name with reusable constants.
- __Item__
  - `\Psr\Cache\CacheItemInterface::set` will also no longer accepts resource object anymore as method unique parameter
- __Misc__
  - Fixed typos in [README.md](./README.md)

## 9.0.0
##### 31 october 2021
- __Migration guide__
  - Read the [migration guide](./docs/migration/MigratingFromV8ToV9.md) to upgrade from V8 to V9
- __PSR-6__
  - Upgraded `psr/cache` dependency to `^2.0||^3.0` (for PHP-8 types)
  - `\Psr\Cache\CacheItemInterface::get()` slightly changed to fully comply with missing PSR-6 specification: If the cache item is **NOT** hit, this method will return `NULL`.
- __PSR-16__
  - Upgraded `psr/simple-cache` dependency to `^2.0||^3.0` (for PHP-8 types)
- __API__
  - Upgraded Phpfastcache API to `4.0.0` ([see changes](CHANGELOG_API.md))
  - Renamed `Api::getPhpFastCacheVersion()` to `Api::getPhpfastcacheVersion()`
  - Renamed `Api::getPhpFastCacheChangelog()` to `Api::getPhpfastcacheChangelog()`
  - Renamed `Api::getPhpFastCacheGitHeadHash()` to `Api::getPhpfastcacheGitHeadHash()`
- __Cluster__
  - Renamed `\Phpfastcache\Cluster\AggregatorInterface::aggregateNewDriver()` to `\Phpfastcache\Cluster\AggregatorInterface::aggregateDriverByName()` 
- __Exceptions__
  - Added `PhpfastcacheEventManagerException` for EventManager-related exceptions
- __Global__
  - Removed magics methods from CacheManager `CacheManager::DriverName()`, use `CacheManager::getInstance('DriverName')` instead
  - `\Phpfastcache\Proxy\PhpfastcacheAbstractProxy` now implements `\Phpfastcache\Proxy\PhpfastcacheAbstractProxyInterface`
  - Slightly increased performances on some critical points of the library
  - Removed "BadPracticeOMeter" notice in CacheManager
  - Removed many code duplicate (like in `\Phpfastcache\Driver\[DRIVER_NAME]\Item` classes)
  - Reworked traits inter-dependencies for better logic and less polymorphic calls in pool/item traits
  - Upgrading library to use benefits of PHP 8 new features (see below)
  - Typed every class properties of the library
  - Migrated many Closure to arrow functions
  - Updated parameters & return type hint to use benefit of covariance and contravariance
  - Removed embedded Autoload, Phpfastcache is now only Composer-compatible.
  - Removed embedded dependencies (`psr/cache`, `psr/simple-cache`)
- __Helpers__
  - Deprecated `\Phpfastcache\Helper\CacheConditionalHelper`, use `\Phpfastcache\CacheContract` instead
  - The `\Phpfastcache\CacheContract` class is now also callable directly without calling `get()` method
- __Config/Options__
  - Configuration object will now be locked once the cache pool instance is running. 
  - Updated `ConfigurationOption` which is no longer an `ArrayObject` class, therefore array-syntax is no longer available.
  - Removed configuration entry `htaccess` for files-based drivers.
  - Removed `IOConfigurationOptionTrait::getHtaccess()`
  - Removed `IOConfigurationOptionTrait::setHtaccess()`
- __Tests__
  - Added PHPMD, PHPCS and PHPSTAN coverages to increase quality of the project
  - Updated tests to work with new core/drivers changes
  - Removed Autoload test since its support has been removed and now only managed by Composer
  - Increased tests reliability and code coverage for better catching any eventual regression 
- __Item__
  - `\Psr\Cache\CacheItemInterface::set` will not accept `\Closure` object anymore as method unique parameter
- __Drivers__
  - Added `Arangodb` driver support
  - Added `Dynamodb` (AWS) driver support
  - Added `Firestore` (GCP) driver support
  - Removed `Cookie` driver because of its potential dangerosity
  - Removed `Couchbase` (SDK 2 support dropped) driver which is now replaced by `Couchbasev3` (SDK 3)
  - Removed `Devtrue` and `Devfalse` drivers
  - Added `Devrandom` with configurable factor chance and data length
  - Renamed classes `\Phpfastcache\Cluster\Drivers\[STATEGY]\[CLUSTER_NAME]Cluster` to `\Phpfastcache\Cluster\Drivers\[STATEGY]\Driver` for better driver naming across the project
- __Events__
  - Added `\Phpfastcache\Event\EventReferenceParameter` class and more events such as driver-specific events, see [EVENTS.md](./docs/EVENTS.md) file for more information
  - Event callbacks will now receive the `eventName` as an extra _last_ callback parameter (except for `onEveryEvents` callbacks)
  - Added `EventManagerInterface::on(array $eventNames, $callback)` method, to subscribe to multiple events in once with the same callback
  - Added method named `unbindAllEventCallbacks(): bool` to `EventManagerInterface` to allow you to unbind/clear all event from an event instance
  - Updated argument type #2 (`$items`) of `onCacheSaveMultipleItems()` event from `ExtendedCacheItemInterface[]` to `EventReferenceParameter($items)`
  - Updated argument type #2 (`$items`) of `onCacheCommitItem()` event from `ExtendedCacheItemInterface[]` to `EventReferenceParameter($items)`
  - Updated argument type #2 (`$value`) of `onCacheItemSet()` event from `mixed` to `EventReferenceParameter(mixed $value)`
- __Misc__
  - Increased minimum PHP compatibility in composer to `^8.0`
  - Updated copyright headers on every file to include the many project contributors
  - Globally renamed every occurrence of `PhpFastCache` to `Phpcastcache`
