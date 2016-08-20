Because the V6 is not backward compatible with the V5 we will help you to migrate your code:


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