Because the V5 is not backward compatible with the V4 we will help you to migrate your code:


### Extending phpFastCache:

#### :clock1: Then:
```php
namespace My\Custom\Project;
use phpFastCache\Core\phpFastCache;

/**
 * Class Cache
 */
class Cache extends phpFastCache
{

}
```

#### :alarm_clock: Now:

```php
namespace My\Custom\Project;
use phpFastCache\Proxy\phpFastCacheAbstractProxy;

/**
 * Class Cache
 */
class Cache extends phpFastCacheAbstractProxy
{

}
```

See examples/extendedPhpFastCache.php for more informations



### Get/Set uses:

#### :clock1: Then:
```php
namespace My\Custom\Project;
use phpFastCache\Core\phpFastCache;

$cache = phpFastCache();
// or
$cache = __c();

$myCacheItem = $cache->get("myKey");

if($myCacheItem === null){
  $myCacheItem = database_operation();
  $cache->set("myKey", $myCacheItem, 600);
}


$template['myCacheItemData'] = $myCacheItem;
```

#### :alarm_clock: Now:

```php
namespace My\Custom\Project;

$config = [
  'path' => 'An\absolute\path',
];
$cache = CacheManager::getInstance('Files', $config);
// or
$cache = CacheManager::Files($config);

$myCacheItem = $cache->getItem("myKey");

if(!$myCacheItem->isHit()){
  $myCacheItem->set(database_operation());
  $cache->save($myCacheItem);
}

$template['myCacheItemData'] = $myCacheItem->get();
```











### Cache clearing:

#### :clock1: Then:
```php
namespace My\Custom\Project;
use phpFastCache\Core\phpFastCache;

$cache = phpFastCache();
// or
$cache = __c();

$cache->clean();
```

#### :alarm_clock: Now:

```php
namespace My\Custom\Project;

$config = [
  'path' => 'An\absolute\path',
];
$cache = CacheManager::getInstance('Files', $config);
// or
$cache = CacheManager::Files($config);

$myCacheItem = $cache->clear();

```












### Search system:
The search system has been removed in favor of Tags features. Remember that the cache must NOT be considered as a search engine like Solr, Sphynx etc.

See examples/tagsMethods.php for more information