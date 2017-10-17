Because the V7 is not backward compatible with the V6, here's a guide to help you to migrate your code:

### Return type & Scalar type declarations 
:anger: :exclamation: The V7 will make use of new php's return type & scalars type declarations features. 
This means that you will now have to be very careful about the data types that you are sending to the phpFastCache API.

### ActOnAll Helper

#### :clock1: Then:
The ActOnAll helper use to return an array of returns only for getters and a strict boolean of CRUD operations:
(
  Files::DeleteItem(): false + 
  Memcache::DeleteItem(): true + 
  Redis::DeleteItem(): true
) => Returning a strict FALSE

#### :alarm_clock: Now:
Whatever you call will now result in an array of returns giving you the possibility to know which driver is returning an unexpected response via the ActOnAll helper.
Also the ActOnAll helper does no longer implements `ExtendedCacheItemPoolInterface` due to the type hint implementation.

### Cache Manager: Instance ID
:new: As of the V7 you can now specify an instance ID using the CacheManager:

```php
CacheManager::getInstance($defaultDriver, $options, $instanceId);
```
Please note that `$instanceId` must be a valid __STRING__.

There's also a variant that throw a `phpFastCacheInstanceNotFoundException` if the instance is not found.
```php
CacheManager::getInstanceById( $instanceId);
```