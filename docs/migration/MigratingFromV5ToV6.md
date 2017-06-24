Because the V6 is not backward compatible with the V5, here's a guide to help you to migrate your code:

### Existing data in cache during migration
:anger: :exclamation: If you update PhpFastCache from v5 to v6 you need to clear up the whole cache otherwise you may get this kind of error:
```
Notice: Undefined index: e in /phpfastcache/src/phpFastCache/Core/Pool/DriverBaseTrait.php on line ...

Fatal error: Uncaught phpFastCache\Exceptions\phpFastCacheInvalidArgumentException: $expiration must be an object implementing the DateTimeInterface in ...
```

### Setting up a default config

#### :clock1: Then:
PhpFastCache used to set a default global config using the `CacheManager::setup()` method

```php
namespace My\Custom\Project;


$instance = CacheManager::setup([
    'path' => 'somewhere'
]);
$instance = CacheManager::getInstance('Files');

```

#### :alarm_clock: Now:
This method has been changed is now replaced by the `CacheManager::setDefaultConfig()` method.
Using the old `CacheManager::setup()` method will trigger a `phpFastCacheInvalidConfigurationException`

```php
namespace My\Custom\Project;


$instance = CacheManager::setDefaultConfig([
    'path' => 'somewhere'
]);
$instance = CacheManager::getInstance('Files');

```

### Type hint of Driver instances

#### :clock1: Then:
Driver instances used to implements a `phpFastCache\Cache\ExtendedCacheItemPoolInterface` interface. 

```php
namespace My\Custom\Project;


$instance = CacheManager::getInstance('Files');

if($instance instanceof \phpFastCache\Cache\ExtendedCacheItemPoolInterface)
{
    // Some code
}

```

#### :alarm_clock: Now:
This has been changed and they now implements a `phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface` interface

```php
namespace My\Custom\Project;


$instance = CacheManager::getInstance('Files');

if($instance instanceof \phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface)
{
    // Some code
}

```

### Type hint of Item instances

#### :clock1: Then:
Item instances used to implements a ``phpFastCache\Cache\ExtendedCacheItemInterface`` interface. 

```php
namespace My\Custom\Project;


$instance = CacheManager::getInstance('Files');
$item = $instance->getItem('key');


if($item instanceof \phpFastCache\Cache\ExtendedCacheItemInterface)
{
    // Some code
}

```

#### :alarm_clock: Now:
This has been changed and it now returns a `phpFastCache\Core\Item\ExtendedCacheItemInterface` interface

```php
namespace My\Custom\Project;


$instance = CacheManager::getInstance('Files');
$item = $instance->getItem('key');


if($item instanceof \phpFastCache\Core\Item\ExtendedCacheItemInterface)
{
    // Some code
}

```

### Catching \InvalidArgumentException

#### :clock1: Then:
Code used to catch a `\InvalidArgumentException` interface. 

```php
namespace My\Custom\Project;

$instance = CacheManager::getInstance('Files');

try{
    $item = $instance->getItem(array());
}catch(\InvalidArgumentException $e){
    //Catched exception code
}

```

#### :alarm_clock: Now:
This has been changed you now MUST catch `\phpFastCache\Exceptions\phpFastCacheInvalidArgumentException` class

```php
namespace My\Custom\Project;

$instance = CacheManager::getInstance('Files');

try{
    $item = $instance->getItem(array());
}catch(\phpFastCache\Exceptions\phpFastCacheInvalidArgumentException $e){
    //Catched exception code
}

```
:warning: Please note that `\phpFastCache\Exceptions\phpFastCacheInvalidArgumentException` implements `\Psr\Cache\InvalidArgumentException` as per PSR-6.

### Catching \LogicException

#### :clock1: Then:
Code used to catch a `\LogicException`. 

```php
namespace My\Custom\Project;

$instance = CacheManager::getInstance('Files');

try{
    $item = $instance->getItem(array());
}catch(\LogicException $e){
    //Catched exception code
}

```

#### :alarm_clock: Now:
This has been changed you now MUST catch `\phpFastCache\Exceptions\phpFastCacheLogicException` interface

```php
namespace My\Custom\Project;

$instance = CacheManager::getInstance('Files');

try{
    $item = $instance->getItem(array());
}catch(\phpFastCache\Exceptions\phpFastCacheLogicException $e){
    //Catched exception code
}

```

### Allowed characters in key identifier
:warning: As of the V6, the following characters can not longer being a part of the key identifier: `{}()/\@:`

If you try to do so, an `\phpFastCache\Exceptions\phpFastCacheInvalidArgumentException` will be raised.

You must replace them with a safe delimiter such as `.|-_`

### Cache clear method
The deprecated method `phpFastCache\Cache\ExtendedCacheItemPoolInterface::clear()` is now definitely removed.


#### :clock1: Then:
In the V5 the method `phpFastCache\Cache\ExtendedCacheItemPoolInterface::clear()` was deprecated.

```php
namespace My\Custom\Project;


$instance = CacheManager::getInstance('Files');

if($instance instanceof \phpFastCache\Cache\ExtendedCacheItemPoolInterface)
{
    $instance->clear();
}

```

#### :alarm_clock: Now:
In the V6 we removed it. Use `phpFastCache\Cache\ExtendedCacheItemPoolInterface::clean()` instead.

```php
namespace My\Custom\Project;


$instance = CacheManager::getInstance('Files');

if($instance instanceof \phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface)
{
    $instance->clean();
}

```

### Mongodb driver has changed
:warning: As of the V6, the Mongodb driver has been updated

#### :clock1: Then:
In the V5 the driver was making use of of the [deprecated class Mongo](http://php.net/manual/fr/class.mongo.php).

#### :alarm_clock: Now:
In the V6 we made an important change: We now make use of [Mongodb Driver](http://php.net/manual/fr/set.mongodb.php)
Only your code configuration will have to be updated, PhpFastCache manages all the abstract part by itself.

### DriverStatistic class name has changed
:warning: As of the V6, DriverStatistic class name has changed for a better classes name normalization

#### :clock1: Then:
In the V5 the class was named `phpFastCache\Entities\driverStatistic`

#### :alarm_clock: Now:
In the V6 it has changed and is now `phpFastCache\Entities\DriverStatistic` (with a **D** uppercase), you may need to re-fetch your
git repository completely on some operating systems (especially Windows, meh).