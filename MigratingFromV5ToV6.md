Because the V6 is not backward compatible with the V5, here's a guide to help you to migrate your code:


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
This has been changed you now MUST catch `\phpFastCache\Exceptions\phpFastCacheInvalidArgumentException` interface

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
