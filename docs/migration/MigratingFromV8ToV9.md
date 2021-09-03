Because the V9 is **relatively** not backward compatible with the V8, here's a guide to help you to migrate your code:

### :warning: Minimum php version increased to 8.0+
As of the V9 the mandatory minimum php version has been increased to 8.0+.
Once released, the php version 8.1 will be unit-tested 

### Embedded autoload has been removed (and therefore, embedded dependencies)
Use [Composer](https://getcomposer.org/doc/03-cli.md#require) to include Phpfastcache in your project

### Deprecated `\Phpfastcache\Helper\CacheConditionalHelper`
Use `\Phpfastcache\CacheContract` instead. See [Wiki](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV9%CB%96%5D-Cache-contract).

###  Removed `Couchbase` driver (SDK 2 support dropped)
It is now replaced by `Couchbasev3` driver (SDK 3), the configuration options are all the same plus `scopeName` and `collectionName` that are now configurable.

###  Updated EventManager instances
- Updated argument type #2 (`$items`) of `onCacheSaveMultipleItems()` event from `ExtendedCacheItemInterface[]` to `EventReferenceParameter($items)`
- Updated argument type #2 (`$items`) of `onCacheCommitItem()` event from `ExtendedCacheItemInterface[]` to `EventReferenceParameter($items)`
- Updated argument type #2 (`$value`) of `onCacheItemSet()` event from `mixed` to `EventReferenceParameter(mixed $value)`

See [EVENTS.md](./../EVENTS.md) file for more information
###  Upgraded Phpfastcache API
The Phpfastcache API has been upgraded to `4.0.0` with BC breaks. [See full changes](./../../CHANGELOG_API.md)

### Removed `Devtrue` and `Devfalse` drivers
They have not been replaced.
However, the `Devrandom` driver with configurable factor chance and data length has been added

------
More information in our comprehensive [changelog](./../../CHANGELOG.md).




