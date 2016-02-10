[![Code Climate](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache/badges/gpa.svg)](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache) [![Build Status](https://travis-ci.org/PHPSocialNetwork/phpfastcache.svg?branch=final)](https://travis-ci.org/PHPSocialNetwork/phpfastcache)
---------------------------
Simple Yet Powerful PHP Caching Class
---------------------------
More information at http://www.phpfastcache.com
One Class uses for All Cache. You don't need to rewrite your code many times again.

Supported: SSDB, Redis, Predis, Cookie, Files, MemCache, MemCached, APC, WinCache, X-Cache, PDO with SQLite

---------------------------
Reduce Database Calls
---------------------------

Your website have 10,000 visitors who are online, and your dynamic page have to send 10,000 same queries to database on every page load.
With phpFastCache, your page only send 1 query to DB, and use the cache to serve 9,999 other visitors.

---------------------------
Rich Development API
---------------------------

phpFastCache offers you a lot of usefull APIS:

- get($keyword) // The getter, obviously, return your cache object
- set($keyword, $something_your_want_to_cache, $time_as_second = 0) // The setter, for those who missed it, put 0 meant cache it forever
- delete($keyword) // For removing a cached thing
- clean() // Allow you to completely empty the cache and restart from the beginning
- touch($keyword, $time_you_want_to_extend) // Allow you to extends the lifetime of an entry without altering the value
- increment($keyword, $step = 1) // For integer that we can count on
- decrement($keyword, $step = 1) // Redundant joke...
- search($string_or_regex, $search_in_value = false | true) // Allow you to perform some search on the cache index
- isExisting($keyword) // Check if your cache entry exists, it is the equivalent of isset()
- stats() // Return the cache statistics, useful for checking disk space used by the cache etc.

Also support Multiple calls, Tagging, Setup Folder for caching. Look at our examples folders.

---------------------------
As Fast To Implement As Opening a Beer
---------------------------


#### :thumbsup: Step 1: Include phpFastCache in your project with composer:


```bash
composer require phpFastCache/phpFastCache
```

#### :construction: Step 2: Setup your website code to implements phpFastCache bits
```php
use phpFastCache\CacheManager;

// Include composer autoloader
require '../vendor/autoload.php';

$cache = CacheManager::Files();

// $cache = CacheManager::Memcached();
// phpFastCache supported: SSDB, Redis, Predis, Cookie, Files, MemCache, MemCached, APC, WinCache, XCache, SQLite
// $cache = CacheManager::getInstance("auto", $config);
// $cache = CacheManager::getInstance("memcached", $server_config);

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$products = $cache->get($key);

if (is_null($products)) {
    $products = "DB QUERIES | FUNCTION_GET_PRODUCTS | ARRAY | STRING | OBJECTS";
    // Write products to Cache in 10 minutes with same keyword
    $cache->set($key, $products, 600);

    echo " --> NO CACHE ---> DB | Func | API RUN FIRST TIME ---> ";

} else {
    echo " --> USE CACHE --> SERV 10,000+ Visitors FROM CACHE ---> ";
}

/**
 * use your products here or return it;
 */
echo $products;

```

#### :floppy_disk: Legacy & Easy Upgrade from Old Version
```php
// In your config files
require_once ('phpFastCache/phpFastCache.php');

$cache = phpFastCache();
// $cache = phpFastCache("files");
// $cache = phpFastCache("memcached");

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$products = $cache->get($key);

// yet it's the same as autoload

```


#### :zap: Step 3: Enjoy ! Your website is now faster than flash !
For curious developpers, there is a lot of others available examples [here](https://github.com/khoaofgod/phpFastCache/tree/final/examples).

#### :boom: phpFastCache support
Found an issue or had an idea ? Come here [here](https://github.com/PHPSocialNetwork/phpfastcache/issues) and let you know !