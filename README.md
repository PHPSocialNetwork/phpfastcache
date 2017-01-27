[![Code Climate](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache/badges/gpa.svg)](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHPSocialNetwork/phpfastcache/badges/quality-score.png?b=final)](https://scrutinizer-ci.com/g/PHPSocialNetwork/phpfastcache/?branch=final) [![Build Status](https://travis-ci.org/PHPSocialNetwork/phpfastcache.svg?branch=final)](https://travis-ci.org/PHPSocialNetwork/phpfastcache) [![Latest Stable Version](http://img.shields.io/packagist/v/phpfastcache/phpfastcache.svg)](https://packagist.org/packages/phpfastcache/phpfastcache) [![Total Downloads](http://img.shields.io/packagist/dt/phpfastcache/phpfastcache.svg)](https://packagist.org/packages/phpfastcache/phpfastcache) [![Dependency Status](https://www.versioneye.com/php/phpfastcache:phpfastcache/badge.svg)](https://www.versioneye.com/php/phpfastcache:phpfastcache) [![License](https://img.shields.io/packagist/l/phpfastcache/phpfastcache.svg)](https://packagist.org/packages/phpfastcache/phpfastcache) [![Coding Standards](https://img.shields.io/badge/CI-PSR6-orange.svg)](https://github.com/php-fig/cache)

:exclamation: V4 USERS, PLEASE SEE THE README !! V5 IS OFFICIALY OUT !! YOUR CODE NEEDS TO BE [REWRITTEN](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV4%5D-Migrating-your-code-to-the-V5) :exclamation:

---------------------------
Simple Yet Powerful PHP Caching Class
---------------------------
More information in [Wiki](https://github.com/PHPSocialNetwork/phpfastcache/wiki)
One Class uses for All Cache. You don't need to rewrite your code many times again.


### Supported drivers at this day *
:bulb: Feel free to propose a driver by making a new **Pull Request**, they are welcome !

|   Regular drivers  | High performances drivers | Development driver |
|--------------------|---------------------------|--------------------|
|  `Apc(u)`          | `CouchBase`               | `Devnull`          |
|  `Cookie`          | `Mongodb`                 | `Devfalse`         |
|  `Files`           | `Predis`                  | `Devtrue`          |
|  `Leveldb`         | `Redis`                   |                    |
|  `Memcache(d)`     | `Ssdb`                    |                    |
|  `Sqlite`          | `Zend Memory Cache`       |                    |
|  `Wincache`        |                           |                    |
|  `Xcache`          |                           |                    |
|  `Zend Disk Cache` |                           |                    |

\* Driver descriptions available in DOCS/DRIVERS.md

### Symfony/Drupal developers are not forgotten !
Starting with v5, phpFastCache comes with a [Symfony Bundle](https://github.com/PHPSocialNetwork/phpfastcache-bundle).
It's fresh, so feel free to report any bug or contribute to the project using pull requests.

Also a [Drupal 8 Module](https://github.com/PHPSocialNetwork/phpfastcache-drupal) is currently in development, add it to your starred projects to get notified of the first public release. 


---------------------------
Not a "Traditional" Caching
---------------------------
phpFastCache is not like the traditional caching methods which keep reading and writing to files, sqlite or keeping open massive amounts of connections to memcache, redis, mongodb... Also, when you use Memcache / Memcached, your miss hits will be reduced.
Different from the usual caching methods you'll find everywhere on the internet, the phpFastCache library reduces high I/O load, and is faster than the traditional caching methods by at least ~7 times.
However, when you still want to use traditional caching methods, we support them too.

```php
use phpFastCache\CacheManager;

CacheManager::getInstance('files', $config);
// An alternative exists:
CacheManager::Files($config);

```

---------------------------
Reduce Database Calls
---------------------------

Your website has 10,000 visitors who are online, and your dynamic page has to send 10,000 times the same queries to database on every page load.
With phpFastCache, your page only sends 1 query to your DB, and uses the cache to serve the 9,999 other visitors.

---------------------------
Rich Development API
---------------------------

phpFastCache offers you a lot of useful APIs:

### Item API
- getKey() // Returns the item identifier (key)
- get() // The getter, obviously, returns your cache object
- set($value) // The setter, for those who missed it, putting 0 means cache it forever
- expiresAfter($ttl) // Allow you to extend the lifetime of an entry without altering its value (formerly known as touch())
- expiresAt($expiration) // Sets the expiration time for this cache item (as a DateTimeInterface object)
- increment($step = 1) // To allow us to count on an integer item
- decrement($step = 1) // Redundant joke...
- append($data) // Appends data to a string or an array (push)
- prepend($data) // Prepends data to a string or an array (unshift)
- isHit() // Checks if your cache entry exists and is still valid, it's the equivalent of isset()
- isExpired() // Checks if your cache entry is expired
- getTtl() // Gets the remaining Time To Live as an integer
- getExpirationDate() // Gets the expiration date as a Datetime object
- addTag($tagName) // Adds a tag
- addTags(array $tagNames) // Adds multiple tags
- setTags(array $tags) // Sets multiple tags
- getTags() // Gets the tags
- getTagsAsString($separator = ', ') // Gets the data as a string separated by $separator
- removeTag($tagName) // Removes a tag
- removeTags(array $tagNames) // Removes some tags
- getDataAsJsonString()// Returns the data as a well-formatted json string

### ItemPool API
- getItem($key) // Retrieves an item and returns an empty item if not found
- getItems(array $keys) // Retrieves one or more items and returns an array of items
- getItemsAsJsonString(array $keys) // Returns a json string that represents an array of items
- hasItem($key) // Tests if an item exists
- deleteItem($key) // Deletes an item
- deleteItems(array $keys) // Deletes one or more items
- save(CacheItemInterface $item) // Persists a cache item immediately
- saveDeferred(CacheItemInterface $item); // Sets a cache item to be persisted later
- commit(); // Persists any deferred cache items
- clear() // Allows you to completely empty the cache
- getStats() // Returns the cache statistics as an object, useful for checking disk space used by the cache etc.
- getItemsByTag($tagName) // Returns items by a tag
- getItemsByTags(array $tagNames) // Returns items by multiple tags
- getItemsByTagsAsJsonString(array $tagNames) // Returns a json string that represents an array of items corresponding to given tags
- deleteItemsByTag($tagName) // Deletes items by a tag
- deleteItemsByTags(array $tagNames) // Deletes items by multiple tags
- incrementItemsByTag($tagName, $step = 1) // Increments items by a tag
- incrementItemsByTags(array $tagNames, $step = 1) // Increments items by multiple tags
- decrementItemsByTag($tagName, $step = 1) // Decrements items by a tag
- decrementItemsByTags(array $tagNames, $step = 1) // Decrements items by multiple tags
- appendItemsByTag($tagName, $data) // Appends items by a tag
- appendItemsByTags(array $tagNames, $data) // Appends items by multiple tags
- prependItemsByTag($tagName, $data) // Prepends items by a tag
- prependItemsByTags(array $tagNames, $data) // Prepends items by multiple tags
- detachItem($item) // Detaches an item from the pool
- detachAllItems($item) // Detaches all items from the pool
- attachItem($item) // (Re-)attaches an item to the pool
- isAttached($item) // Verifies if an item is (still) attached

It also supports Multiple calls, Tagging, Setup Folder for caching. Look at our examples folders for more information.

---------------------------
As Fast To Implement As Opening a Beer
---------------------------


#### :thumbsup: Step 1: Include phpFastCache in your project with composer:


```bash
composer require phpFastCache/phpFastCache
```

#### :construction: Step 2: Setup your website code to implement the phpFastCache calls (with Composer)
```php
use phpFastCache\CacheManager;

// Setup File Path on your config files
CacheManager::setup(array(
    "path" => '/var/www/phpfastcache.com/dev/tmp', // or in windows "C:/tmp/"
));

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
    // etc...
];

if (is_null($CachedString->get())) {
    $CachedString->set($your_product_data)->expiresAfter(5);//in seconds, also accepts Datetime
	$InstanceCache->save($CachedString); // Save the cache item just like you do with doctrine and entities

    echo "FIRST LOAD // WROTE OBJECT TO CACHE // RELOAD THE PAGE AND SEE // ";
    echo $CachedString->get();

} else {
    echo "READ FROM CACHE // ";
    echo $CachedString->get()[0];// Will print 'First product'
}

/**
 * use your products here or return them;
 */
echo implode('<br />', $CachedString->get());// Will echo your product list

```

##### :floppy_disk: Legacy / Lazy Method (Without Composer)
* See the file examples/legacy.php for more information.

#### :zap: Step 3: Enjoy ! Your website is now faster than lightning !
For curious developpers, there is a lot of other examples available  [here](https://github.com/PHPSocialNetwork/phpfastcache/tree/final/examples).

#### :boom: phpFastCache support
Found an issue or have an idea ? Come [here](https://github.com/PHPSocialNetwork/phpfastcache/issues) and let us know !
