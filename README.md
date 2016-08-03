[![Code Climate](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache/badges/gpa.svg)](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHPSocialNetwork/phpfastcache/badges/quality-score.png?b=final)](https://scrutinizer-ci.com/g/PHPSocialNetwork/phpfastcache/?branch=final) [![Build Status](https://travis-ci.org/PHPSocialNetwork/phpfastcache.svg?branch=final)](https://travis-ci.org/PHPSocialNetwork/phpfastcache) [![Latest Stable Version](http://img.shields.io/packagist/v/phpfastcache/phpfastcache.svg)](https://packagist.org/packages/phpfastcache/phpfastcache) [![Total Downloads](http://img.shields.io/packagist/dt/phpfastcache/phpfastcache.svg)](https://packagist.org/packages/phpfastcache/phpfastcache) [![Dependency Status](https://www.versioneye.com/php/phpfastcache:phpfastcache/badge.svg)](https://www.versioneye.com/php/phpfastcache:phpfastcache) [![License](https://img.shields.io/packagist/l/phpfastcache/phpfastcache.svg)](https://packagist.org/packages/phpfastcache/phpfastcache) [![Coding Standards](https://img.shields.io/badge/CI-PSR6-orange.svg)](https://github.com/php-fig/cache) 

:exclamation: V4 USERS, PLEASE SEE THE README !! THE V5 IS OFFICIALY OUT !! YOUR CODE NEEDS TO BE REWRITTEN :exclamation:

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

### Symfony developers are not forgotten !
Starting of the v5, phpFastCache comes with a [Symfony Bundle](https://github.com/PHPSocialNetwork/phpfastcache-bundle).
He's fresh, so feel free to report any bug or contribute to the code using pull requests.

---------------------------
Not a "Traditional" Caching
---------------------------
phpFastCache is not a traditional caching method which is keep read and write to files, sqlite or mass connections to memcache, redis, mongodb... Also, when you use Memcache / Memcached, your miss hits will be reduce.
Different with normal caching methods which shared everywhere on internet, phpFastCache Lib reduce the high I/O load, and faster than traditional caching method at least x7 ~+ times.
However, some time you still want to use traditional caching, we support them too.

```php
use phpFastCache\CacheManager;

CacheManager::getInstance('files', $config);
// An alternative exists:
CacheManager::Files($config);

```

---------------------------
Reduce Database Calls
---------------------------

Your website have 10,000 visitors who are online, and your dynamic page have to send 10,000 same queries to database on every page load.
With phpFastCache, your page only send 1 query to DB, and use the cache to serve 9,999 other visitors.

---------------------------
Rich Development API
---------------------------

phpFastCache offers you a lot of useful APIs:

### Item API
- getKey() // Return the item identifier (key)
- get() // The getter, obviously, return your cache object
- set($value) // The setter, for those who missed it, put 0 meant cache it forever
- expiresAfter($ttl) // Allow you to extends the lifetime of an entry without altering the value (formerly known as touch())
- expiresAt($expiration) // Sets the expiration time for this cache item (as a DateTimeInterface object)
- increment($step = 1) // For integer that we can count on
- decrement($step = 1) // Redundant joke...
- append($data) // Append data to a string or an array (push)
- prepend($data) // Prepend data to a string or an array (unshift)
- isHit() // Check if your cache entry exists and is still valid, it is the equivalent of isset()
- isExpired() // Check if your cache entry is expired
- getTtl() // Get the remaining Time To Live as an integer
- getExpirationDate() // Get the expiration date as a Datetime object
- addTag($tagName) // Add a tag
- addTags(array $tagNames) // Add many tags
- setTags(array $tags) // Set some tags
- getTags() // Get the tags
- getTagsAsString($separator = ', ') // Get the data a string separated by $separator
- removeTag($tagName) // Remove a tag
- removeTags(array $tagNames) // Remove some tags
- getDataAsJsonString()// Return the data as a well-formatted json string

### ItemPool API
- getItem($key) // Retrieve an item and returns an empty item if not found
- getItems(array $keys) // Retrieve one or more item and returns an array of items
- getItemsAsJsonString(array $keys) // Returns A json string that represents an array of items
- hasItem($key) // Tests if an item exists
- deleteItem($key) // Delete an item
- deleteItems(array $keys) // Delete one or more items
- save(CacheItemInterface $item) // Persists a cache item immediately
- saveDeferred(CacheItemInterface $item); // Sets a cache item to be persisted later
- commit(); // Persists any deferred cache items
- clear() // Allow you to completely empty the cache and restart from the beginning
- getStats() // Return the cache statistics as an object, useful for checking disk space used by the cache etc.
- getItemsByTag($tagName) // Return items by a tag
- getItemsByTags(array $tagNames) // Return items by some tags
- getItemsByTagsAsJsonString(array $tagNames) // Returns A json string that represents an array of items by tags-based
- deleteItemsByTag($tagName) // Delete items by a tag
- deleteItemsByTags(array $tagNames) // Delete items by some tags
- incrementItemsByTag($tagName, $step = 1) // Increment items by a tag
- incrementItemsByTags(array $tagNames, $step = 1) // Increment items by some tags
- decrementItemsByTag($tagName, $step = 1) // Decrement items by a tag
- decrementItemsByTags(array $tagNames, $step = 1) // Decrement items by some tags
- appendItemsByTag($tagName, $data) // Append items by a tag
- appendItemsByTags(array $tagNames, $data) // Append items by some tags
- prependItemsByTag($tagName, $data) // Prepend items by a tag
- prependItemsByTags(array $tagNames, $data) // Prepend items by some tags
- detachItem($item) // Detach an item from the pool
- detachAllItems($item) // Detach all items from the pool
- attachItem($item) // (Re-)attach an item to the pool
- isAttached($item) // Verify if an item is (still) attached

Also support Multiple calls, Tagging, Setup Folder for caching. Look at our examples folders.

---------------------------
As Fast To Implement As Opening a Beer
---------------------------


#### :thumbsup: Step 1: Include phpFastCache in your project with composer:


```bash
composer require phpFastCache/phpFastCache
```

#### :construction: Step 2: Setup your website code to implements phpFastCache bits (With Composer)
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
    echo $CachedString->get()[0];// Will prints 'First product'
}

/**
 * use your products here or return it;
 */
echo implode('<br />', $CachedString->get());// Will echo your product list

```

##### :floppy_disk: Legacy / Lazy Method (Without Composer)
* See the file examples/legacy.php for more information.

#### :zap: Step 3: Enjoy ! Your website is now faster than flash !
For curious developpers, there is a lot of others available examples [here](https://github.com/PHPSocialNetwork/phpfastcache/tree/final/examples).

#### :boom: phpFastCache support
Found an issue or had an idea ? Come [here](https://github.com/PHPSocialNetwork/phpfastcache/issues) and let us know !
