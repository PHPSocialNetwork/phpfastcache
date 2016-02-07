[![Code Climate](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache/badges/gpa.svg)](https://codeclimate.com/github/PHPSocialNetwork/phpfastcache) [![Build Status](https://travis-ci.org/PHPSocialNetwork/phpfastcache.svg?branch=final)](https://travis-ci.org/PHPSocialNetwork/phpfastcache)
---------------------------
Simple Yet Powerful PHP Caching Class
---------------------------
More information at http://www.phpfastcache.com
One Class uses for All Cache. You don't need to rewrite your code many times again.

Supported: Redis, Predis, Cookie, Files, MemCache, MemCached, APC, WinCache, X-Cache, PDO with SQLite

---------------------------
Reduce Database Calls
---------------------------

Your website have 10,000 visitors who are online, and your dynamic page have to send 10,000 same queries to database on every page load.
With phpFastCache, your page only send 1 query to DB, and use the cache to serve 9,999 other visitors.

---------------------------
Rich Development API
---------------------------

Phpfastcache offers you a lot of usefull APIS:

- get() // The getter, obviously
- set() // The setter, for those who missed it
- delete() // For removing a cached thing
- clean() // Allow you to completely empty the cache and restart from the beginning
- touch() // Allow you to extends the lifetime of an entry without altering the value 
- increment() // For integer that we can count on
- decrement() // Redundant joke...
- search() // Allow you to perform some search on the cache index
- isExisting() // Check if your cache entry exists, it is the equivalent of isset()
- stats() // Return the cache statistics, useful for checking disk space used by the cache etc.

---------------------------
As Fast To Implement As Opening a Beer
---------------------------


#### Step 1: Include phpfastcache in your project with composer:


```bash
composer require phpfastcache/phpfastcache
```

#### Step 2: Setup your website code to implements phpfastcache bits
```php
use Phpfastcache\core\InstanceManager;

// Include composer autoloader
require '../vendor/autoload.php';

$cache = InstanceManager::getInstance();

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

#### Legacy & Easy Upgrade from Old Version
```php
// In your config files
require_once ('Phpfastcache/phpfastcache.php');

$cache = phpFastCache();

/**
 * Try to get $products from Caching First
 * product_page is "identity keyword";
 */
$key = "product_page";
$products = $cache->get($key);

// yet it's the same as autoload

```


#### Step 3: Enjoy ! Your website is now faster than flash !

For curious developpers, there is a lot of others available examples [here](https://github.com/khoaofgod/phpfastcache/tree/final/examples)