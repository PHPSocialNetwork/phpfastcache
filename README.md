[![Code Climate](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache/badges/gpa.svg)](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache) [![Build Status](https://travis-ci.org/PHPSocialNetwork/phpfastcache.svg?branch=final)](https://travis-ci.org/PHPSocialNetwork/phpfastcache) [![Latest Stable Version](http://img.shields.io/packagist/v/phpfastcache/phpfastcache.svg)](https://packagist.org/packages/phpfastcache/phpfastcache) [![Total Downloads](http://img.shields.io/packagist/dt/phpfastcache/phpfastcache.svg)](https://packagist.org/packages/phpfastcache/phpfastcache) [![License](https://img.shields.io/packagist/l/phpfastcache/phpfastcache.svg)](https://packagist.org/packages/phpfastcache/phpfastcache) 
---------------------------
Simple Yet Powerful PHP Caching Class
---------------------------
More information at http://www.phpfastcache.com
One Class uses for All Cache. You don't need to rewrite your code many times again.

Supported: SSDB, Redis, Predis, Cookie, Files, MemCache, MemCached, APC, WinCache, X-Cache, PDO with SQLite

---------------------------
Not a "Traditional" Caching
---------------------------
phpFastCache is not a traditional caching method which is keep read and write to files, sqlite or mass connections to memcache, redis, mongodb... phpFastCache use my unique caching method.
When you use Files, and Sqlite, I am guarantee you still can get fast speed almost like memcache & redis for sure. Also, when you use Memcache / Memcached, your miss hits will be reduce.
Different with normal caching methods which shared everywhere on internet, phpFastCache Lib reduce the high I/O load, and faster than traditional caching method at least x7 - 10 times.
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

phpFastCache offers you a lot of usefull APIS:

### Item API
- get() // The getter, obviously, return your cache object
- set($something_your_want_to_cache, $time_as_second = 0) // The setter, for those who missed it, put 0 meant cache it forever
- touch($time_you_want_to_extend) // Allow you to extends the lifetime of an entry without altering the value
- increment($step = 1) // For integer that we can count on
- decrement($step = 1) // Redundant joke...
- isHit() // Check if your cache entry exists and is still valid, it is the equivalent of isset()

### ItemPool API
- delete() // For removing a cached thing
- clean() // Allow you to completely empty the cache and restart from the beginning
- search($string_or_regex, $search_in_value = false | true) // Allow you to perform some search on the cache index
- stats() // Return the cache statistics as an object, useful for checking disk space used by the cache etc.
- searchByTag() // For searching by tag 
- searchByKey() // For searching by key 
- searchByValue() // For searching by value (slow) 

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
    "path" => '/var/www/phpfastcache.dev.geolim4.com/geolim4/tmp', // or in windows "C:/tmp/"
));

// In your class, function, you can call the Cache
$InstanceCache = CacheManager::getInstance('files');
// OR $InstanceCache = CacheManager::getInstance() <-- open examples/global.setup.php to see more

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

#### :floppy_disk: Legacy / Lazy Method (Without Composer)
See the file examples/legacy.php for more information.

#### :zap: Step 3: Enjoy ! Your website is now faster than flash !
For curious developpers, there is a lot of others available examples [here](https://github.com/khoaofgod/phpFastCache/tree/final/examples).

#### :boom: phpFastCache support
Found an issue or had an idea ? Come here [here](https://github.com/PHPSocialNetwork/phpfastcache/issues) and let you know !
