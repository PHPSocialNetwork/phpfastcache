[![Total Downloads](https://img.shields.io/packagist/dt/phpfastcache/phpfastcache.svg?maxAge=86400)](https://packagist.org/packages/phpfastcache/phpfastcache) [![Latest Stable Version](https://img.shields.io/packagist/v/phpfastcache/phpfastcache.svg?maxAge=86400)](https://packagist.org/packages/phpfastcache/phpfastcache) [![PHPSTAN](https://img.shields.io/badge/PHPSTAN-L6-blue.svg?maxAge=86400)](https://github.com/PHPSocialNetwork/phpfastcache/blob/master/.travis.yml) [![Cache Interface](https://img.shields.io/badge/CI-PSR6-orange.svg?maxAge=86400)](https://github.com/php-fig/cache) [![Extended Coding Style](https://img.shields.io/badge/ECS-PSR12-orange.svg?maxAge=86400)](https://www.php-fig.org/psr/psr-12/)  [![Simple Cache](https://img.shields.io/badge/SC-PSR16-orange.svg?maxAge=86400)](https://github.com/php-fig/simple-cache) 
[![Code Climate](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache/badges/gpa.svg)](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHPSocialNetwork/phpfastcache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PHPSocialNetwork/phpfastcache/?branch=master) [![Build Status](https://travis-ci.com/PHPSocialNetwork/phpfastcache.svg?branch=master)](https://app.travis-ci.com/github/PHPSocialNetwork/phpfastcache) [![Semver compliant](https://img.shields.io/badge/Semver-2.0.0-yellow.svg?maxAge=86400)](https://semver.org/spec/v2.0.0.html) [![License](https://img.shields.io/packagist/l/phpfastcache/phpfastcache.svg?maxAge=86400)](https://packagist.org/packages/phpfastcache/phpfastcache) [![Patreon](https://img.shields.io/badge/Support%20us%20on-Patreon-f96854.svg?maxAge=86400)](https://www.patreon.com/geolim4)

#### :warning: Please note that the V9 is mostly a PHP 8 type aware update of Phpfastcache with some significant changes !
> As the V9 is **relatively** not compatible with previous versions, please read carefully the [migration guide](./docs/migration/MigratingFromV8ToV9.md) to ensure you the smoothest migration possible.
One of the biggest change is the configuration system which is now an object that replace the primitive array that we used to implement back then. 
Also, please note that the V9 requires at least PHP 8 or higher to works properly.

---------------------------
Simple Yet Powerful PHP Caching Class
---------------------------
More information in [Wiki](https://github.com/PHPSocialNetwork/phpfastcache/wiki)
The simplicity of abstraction: One class for many backend cache. You don't need to rewrite your code many times again.

### Supported drivers at this day *
:bulb: Feel free to propose a driver by making a new **[Pull Request](https://github.com/PHPSocialNetwork/phpfastcache/compare)**, they are welcome !

| Regular drivers                                                        | High performances drivers                                  |    Development drivers        |    Cluster-Aggregated drivers     |
|------------------------------------------------------------------------|------------------------------------------------------------|-------------------------------|-----------------------------------|
| `Apcu` _(APC support removed)_                                         | `Arangodb`                                                 | `Devnull`                     | `FullReplicationCluster`          |
| `Dynamodb` (AWS)                                                       | `Cassandra`                                                | `Devrandom`                   | `SemiReplicationCluster`          |
| `Files`                                                                | `CouchBasev3`<br>_(`Couchbase` for SDK 2 support removed)_ | `Memstatic`                   | `MasterSlaveReplicationCluster`   |
| `Firestore` (GCP)                                                      | `Couchdb`                                                  |                               | `RandomReplicationCluster`        |
| `Leveldb`                                                              | `Mongodb`                                                  |                               |                                   |
| `Memcache(d)`                                                          | `Predis`                                                   |                               |                                   |
| `Solr` _(Via [Solarium 6.x](https://github.com/solariumphp/solarium))_ | `Redis`                                                    |                               |                                   |
| `Sqlite`                                                               | `Ssdb`                                                     |                               |                                   |
| `Wincache`                                                             | `Zend Memory Cache`                                        |                               |                                   |
| `Zend Disk Cache`                                                      |                                                            |                               |                                   |                                                                     |                                                            |                               |                                   |

\* Driver descriptions available in [DOCS/DRIVERS.md](./docs/DRIVERS.md)

---------------------------
Because caching does not mean weaken your code
---------------------------
Phpfastcache has been developed over the years with 3 main goals:

- Performance: We optimized and still optimize the code to provide you the lightest library as possible
- Security: Because caching strategies can sometimes comes with unwanted vulnerabilities, we do our best to provide you a sage & strong library as possible 
- Portability: No matter what operating system you're working on, we did our best to provide you the most cross-platform code as possible

---------------------------
Rich Development API
---------------------------

Phpfastcache provides you a lot of useful APIs:

### Item API (ExtendedCacheItemInterface)

| Method                               | Return                        |  Description                                                                                                                           | 
|--------------------------------------|-------------------------------|----------------------------------------------------------------------------------------------------------------------------------------| 
| `addTag($tagName)`                   | `ExtendedCacheItemInterface`  |  Adds a tag                                                                                                                            | 
| `addTags(array $tagNames)`           | `ExtendedCacheItemInterface`  |  Adds multiple tags                                                                                                                    | 
| `append($data)`                      | `ExtendedCacheItemInterface`  |  Appends data to a string or an array (push)                                                                                           | 
| `decrement($step = 1)`               | `ExtendedCacheItemInterface`  |  Redundant joke...                                                                                                                     | 
| `expiresAfter($ttl)`                 | `ExtendedCacheItemInterface`  |  Allows you to extends the lifetime of an entry without altering its value (formerly known as touch())                                 | 
| `expiresAt($expiration)`             | `ExtendedCacheItemInterface`  |  Sets the expiration time for this cache item (as a DateTimeInterface object)                                                          | 
| `get()`                              | `mixed`                       |  The getter, obviously, returns your cache object                                                                                      | 
| `getCreationDate()`                  | `\DatetimeInterface`          |  Gets the creation date for this cache item (as a DateTimeInterface object)  *                                                         | 
| `getDataAsJsonString()`              | `string`                      |  Return the data as a well-formatted json string                                                                                       | 
| `getEncodedKey()`                    | `string`                      |  Returns the final and internal item identifier (key), generally used for debug purposes                                               | 
| `getExpirationDate()`                | `ExtendedCacheItemInterface`  |  Gets the expiration date as a Datetime object                                                                                         | 
| `getKey()`                           | `string`                      |  Returns the item identifier (key)                                                                                                     | 
| `getLength()`                        | `int`                         |  Gets the data length if the data is a string, array, or objects that implement `\Countable` interface.                                  | 
| `getModificationDate()`              | `\DatetimeInterface`          |  Gets the modification date for this cache item (as a DateTimeInterface object) *                                                      | 
| `getTags()`                          | `string[]`                    |  Gets the tags                                                                                                                         | 
| `getTagsAsString($separator = ', ')` | `string`                      |  Gets the data as a string separated by $separator                                                                                     | 
| `getTtl()`                           | `int`                         |  Gets the remaining Time To Live as an integer                                                                                         | 
| `increment($step = 1)`               | `ExtendedCacheItemInterface`  |  To allow us to count on an integer item                                                                                               | 
| `isEmpty()`                          | `bool`                        |  Checks if the data is empty or not despite the hit/miss status.                                                                       | 
| `isExpired()`                        | `bool`                        |  Checks if your cache entry is expired                                                                                                 | 
| `isHit()`                            | `bool`                        |  Checks if your cache entry exists and is still valid, it's the equivalent of isset()                                                  | 
| `isNull()`                           | `bool`                        |  Checks if the data is null or not despite the hit/miss status.                                                                        | 
| `prepend($data)`                     | `ExtendedCacheItemInterface`  |  Prepends data to a string or an array (unshift)                                                                                       | 
| `removeTag($tagName)`                | `ExtendedCacheItemInterface`  |  Removes a tag                                                                                                                         | 
| `removeTags(array $tagNames)`        | `ExtendedCacheItemInterface`  |  Removes multiple tags                                                                                                                 | 
| `set($value)`                        | `ExtendedCacheItemInterface`  |  The setter, for those who missed it, can be anything except resources or non-serializer object (ex: PDO objects, file pointers, etc). | 
| `setCreationDate($expiration)`       | `\DatetimeInterface`          |  Sets the creation date for this cache item (as a DateTimeInterface object) *                                                          | 
| `setEventManager($evtMngr)`          | `ExtendedCacheItemInterface`  |  Sets the event manager                                                                                                                | 
| `setExpirationDate()`                | `ExtendedCacheItemInterface`  |  Alias of expireAt() (for more code logic)                                                                                             | 
| `setModificationDate($expiration)`   | `\DatetimeInterface`          |  Sets the modification date for this cache item (as a DateTimeInterface object) *                                                      | 
| `setTags(array $tags)`               | `ExtendedCacheItemInterface`  |  Sets multiple tags                                                                                                                    | 

\* Require configuration directive "itemDetailedDate" to be enabled, else a \LogicException will be thrown

### ItemPool API (ExtendedCacheItemPoolInterface)
| Methods (By Alphabetic Order)                                   | Return                            | Description                                                                                      | 
|-----------------------------------------------------------------|-----------------------------------|--------------------------------------------------------------------------------------------------| 
| `appendItemsByTag($tagName, $data)`                             | `bool`                            | Appends items by a tag                                                                           | 
| `appendItemsByTags(array $tagNames, $data)`                     | `bool`                            | Appends items by one of multiple tag names                                                       | 
| `attachItem($item)`                                             | `void`                            | (Re-)attaches an item to the pool                                                                | 
| `clear()`                                                       | `bool`                            | Allows you to completely empty the cache and restart from the beginning                          | 
| `commit()`                                                      | `bool`                            | Persists any deferred cache items                                                                | 
| `decrementItemsByTag($tagName, $step = 1)`                      | `bool`                            | Decrements items by a tag                                                                        | 
| `decrementItemsByTags(array $tagNames, $step = 1)`              | `bool`                            | Decrements items by one of multiple tag names                                                    | 
| `deleteItem($key)`                                              | `bool`                            | Deletes an item                                                                                  | 
| `deleteItems(array $keys)`                                      | `bool`                            | Deletes one or more items                                                                        | 
| `deleteItemsByTag($tagName)`                                    | `bool`                            | Deletes items by a tag                                                                           | 
| `deleteItemsByTags(array $tagNames, int $strategy)`             | `bool`                            | Deletes items  by one of multiple tag names                                                      | 
| `detachItem($item)`                                             | `void`                            | Detaches an item from the pool                                                                   | 
| `getConfig()`                                                   | `ConfigurationOption`             | Returns the configuration object                                                                 | 
| `getConfigOption($optionName);`                                 | `mixed`                           | Returns a configuration value by its key `$optionName`                                           | 
| `getDefaultConfig()`                                            | `ConfigurationOption`             | Returns the default configuration object (not altered by the object instance)                    | 
| `getDriverName()`                                               | `string`                          | Returns the current driver name (without the namespace)                                          | 
| `getEventManager()`                                             | `EventManagerInterface`           | Gets the event manager                                                                           |
| `getHelp()`                                                     | `string`                          | Provides a very basic help for a specific driver                                                 | 
| `getInstanceId()`                                               | `string`                          | Returns the instance ID                                                                          | 
| `getItem($key)`                                                 | `ExtendedCacheItemInterface`      | Retrieves an item and returns an empty item if not found                                         | 
| `getItems(array $keys)`                                         | `ExtendedCacheItemInterface[]`    | Retrieves one or more item and returns an array of items                                         | 
| `getItemsAsJsonString(array $keys)`                             | `string`                          | Returns A json string that represents an array of items                                          | 
| `getItemsByTag($tagName, $strategy)`                            | `ExtendedCacheItemInterface[]`    | Returns items by a tag                                                                           | 
| `getItemsByTags(array $tagNames, $strategy)`                    | `ExtendedCacheItemInterface[]`    | Returns items by one of multiple tag names                                                       | 
| `getItemsByTagsAsJsonString(array $tagNames, $strategy)`        | `string`                          | Returns A json string that represents an array of items corresponding                            | 
| `getStats()`                                                    | `DriverStatistic`                 | Returns the cache statistics as an object, useful for checking disk space used by the cache etc. | 
| `hasEventManager()`                                             | `bool`                            | Check the event manager                                                                          |
| `hasItem($key)`                                                 | `bool`                            | Tests if an item exists                                                                          | 
| `incrementItemsByTag($tagName, $step = 1, $strategy)`           | `bool`                            | Increments items by a tag                                                                        | 
| `incrementItemsByTags(array $tagNames, $step = 1, $strategy)`   | `bool`                            | Increments items by one of multiple tag names                                                    | 
| `isAttached($item)`                                             | `bool`                            | Verify if an item is (still) attached                                                            | 
| `prependItemsByTag($tagName, $data, $strategy)`                 | `bool`                            | Prepends items by a tag                                                                          | 
| `prependItemsByTags(array $tagNames, $data, $strategy)`         | `bool`                            | Prepends items by one of multiple tag names                                                      | 
| `save(CacheItemInterface $item)`                                | `bool`                            | Persists a cache item immediately                                                                | 
| `saveDeferred(CacheItemInterface $item)`                        | `bool`                            | Sets a cache item to be persisted later                                                          | 
| `saveMultiple(...$items)`                                       | `bool`                            | Persists multiple cache items immediately                                                        | 
| `setEventManager(EventManagerInterface $evtMngr)`               | `ExtendedCacheItemPoolInterface`  | Sets the event manager                                                                           |

:new: in **V8**: Multiple strategies (`$strategy`) are now supported for tagging:
- `TaggableCacheItemPoolInterface::TAG_STRATEGY_ONE` allows you to get cache item(s) by at least **ONE** of the specified matching tag(s). **Default behavior.**
- `TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL` allows you to get cache item(s) by **ALL** of the specified matching tag(s) (the cache item *can* have additional tag(s))
- `TaggableCacheItemPoolInterface::TAG_STRATEGY_ONLY` allows you to get cache item(s) by **ONLY** the specified matching tag(s) (the cache item *cannot* have additional tag(s))
 
It also supports multiple calls, Tagging, Setup Folder for caching. Look at our examples folders for more information.

### Phpfastcache versioning API
Phpfastcache provides a class that gives you basic information about your Phpfastcache installation
- Get the API version (Item+Pool interface) with `Phpfastcache\Api::GetVersion();`
- Get the API changelog (Item+Pool interface) `Phpfastcache\Api::getChangelog();`
- Get the Phpfastcache version with `Phpfastcache\Api::getPhpfastcacheVersion();`
- Get the Phpfastcache changelog `Phpfastcache\Api::getPhpfastcacheChangelog();`

---------------------------
Want to keep it simple ?
---------------------------
:sweat_smile: Good news, as of the V6, a Psr16 adapter is provided to keep the cache simplest using very basic getters/setters:

- `get($key, $default = null);`
- `set($key, $value, $ttl = null);`
- `delete($key);`
- `clear();`
- `getMultiple($keys, $default = null);`
- `setMultiple($values, $ttl = null);`
- `deleteMultiple($keys);`
- `has($key);`

Basic usage:
```php
<?php

use Phpfastcache\Helper\Psr16Adapter;

$defaultDriver = 'Files';
$Psr16Adapter = new Psr16Adapter($defaultDriver);

if(!$Psr16Adapter->has('test-key')){
    // Setter action
    $data = 'lorem ipsum';
    $Psr16Adapter->set('test-key', 'lorem ipsum', 300);// 5 minutes
}else{
    // Getter action
    $data = $Psr16Adapter->get('test-key');
}


/**
* Do your stuff with $data
*/
```

Internally, the Psr16 adapter calls the Phpfastcache Api via the cache manager. 

---------------------------
Introducing to events
---------------------------

:mega: As of the V6, Phpfastcache provides an event mechanism.
You can subscribe to an event by passing a Closure to an active event:

```php
<?php

use Phpfastcache\EventManager;

/**
* Bind the event callback
*/
EventManager::getInstance()->onCacheGetItem(function(ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item){
    $item->set('[HACKED BY EVENT] ' . $item->get());
});

```

An event callback can get unbind but you MUST provide a name to the callback previously:

```php
<?php
use Phpfastcache\EventManager;

/**
* Bind the event callback
*/
EventManager::getInstance()->onCacheGetItem(function(ExtendedCacheItemPoolInterface $itemPool, ExtendedCacheItemInterface $item){
    $item->set('[HACKED BY EVENT] ' . $item->get());
}, 'myCallbackName');


/**
* Unbind the event callback
*/
EventManager::getInstance()->unbindEventCallback('onCacheGetItem', 'myCallbackName');

```
:new: As of the **V8** you can simply subscribe to **every** events of Phpfastcache.

More information about the implementation and the events are available on the [Wiki](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%CB%96%5D-Introducing-to-events)

---------------------------
Introducing new helpers
---------------------------
:books: As of the V6, Phpfastcache provides some helpers to make your code easier.

- (:warning: Removed in v8, [why ?](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%CB%96%5D-Act-on-all-instances)) ~~The [ActOnAll Helper](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%CB%96%5D-Act-on-all-instances) to help you to act on multiple instance at once.~~
- The [CacheConditional Helper](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%CB%96%5D-Cache-Conditional) to help you to make the basic conditional statement more easier.
- The [Psr16 adapter](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%CB%96%5D-Psr16-adapter) 

May more will come in the future, feel free to contribute !

---------------------------
Introducing aggregated cluster support
---------------------------
Check out the [WIKI](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV8%CB%96%5D-Aggregated-cache-cluster) to learn how to implement aggregated cache clustering feature.

---------------------------
As Fast To Implement As Opening a Beer
---------------------------


#### :thumbsup: Step 1: Include phpFastCache in your project with composer:


```bash
composer require phpfastcache/phpfastcache
```

#### :construction: Step 2: Setup your website code to implement the phpFastCache calls (with Composer)
```php
<?php
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;

// Setup File Path on your config files
// Please note that as of the V6.1 the "path" config 
// can also be used for Unix sockets (Redis, Memcache, etc)
CacheManager::setDefaultConfig(new ConfigurationOption([
    'path' => '/var/www/phpfastcache.com/dev/tmp', // or in windows "C:/tmp/"
]));

// In your class, function, you can call the Cache
$InstanceCache = CacheManager::getInstance('files');

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$CachedString = $InstanceCache->getItem($key);

$your_product_data = [
    'First product',
    'Second product',
    'Third product'
     /* ... */
];

if (!$CachedString->isHit()) {
    $CachedString->set($your_product_data)->expiresAfter(5);//in seconds, also accepts Datetime
	$InstanceCache->save($CachedString); // Save the cache item just like you do with doctrine and entities

    echo 'FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // ';
    echo $CachedString->get();

} else {
    echo 'READ FROM CACHE // ';
    echo $CachedString->get()[0];// Will print 'First product'
}

/**
 * use your products here or return them;
 */
echo implode('<br />', $CachedString->get());// Will echo your product list

```

##### :floppy_disk: Legacy support (Without Composer)
~~* See the file examples/withoutComposer.php for more information.~~\
:warning: The legacy autoload will be removed in the next major release :warning:\
Please include Phpfastcache through composer by running `composer require phpfastcache/phpfastcache`.

#### :zap: Step 3: Enjoy ! Your website is now faster than lightning !
For curious developers, there is a lot of other examples available [here](./docs/examples).

#### :boom: Phpfastcache support
Found an issue or have an idea ? Come **[here](https://github.com/PHPSocialNetwork/phpfastcache/issues)** and let us know !
