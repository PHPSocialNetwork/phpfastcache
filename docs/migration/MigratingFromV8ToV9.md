Because the V9 is **relatively** not backward compatible with the V8, here's a guide to help you to migrate your code:

### :warning: Minimum php version increased to 8.0+
As of the V9 the mandatory minimum php version has been increased to 8.0+.
Once released, the php version 8.1 will be unit-tested 

### Embedded autoload has been removed (and therefore, embedded dependencies too)
Use [Composer](https://getcomposer.org/doc/03-cli.md#require) to include Phpfastcache in your project

### Removed magics methods from CacheManager `CacheManager::DriverName()`
Use `CacheManager::getInstance('DriverName')` instead.\
This decision was made to make the cache manager more consistent by removing the "old legacy code". 

### Updated `ConfigurationOption` which is no longer an `ArrayObject` class
You can no longer use the following array-compatible syntax: `$config['yourKey'] = 'value'`\
Use the object-notation syntax instead: `$config->setYourKey('value')`

However, this syntax is STILL valid through the configuration constructor\
For the default config object: `new ConfigurationOption(['yourKey' => 'yourValue'])`\
Or for specific config objects: `new \Phpfastcache\Drivers\Files\Config(['yourKey' => 'yourValue'])`\
Finally, the config name you try to set MUST be recognized or an exception will be thrown. 

### Deprecated `\Phpfastcache\Helper\CacheConditionalHelper`
Use `\Phpfastcache\CacheContract` instead. See [Wiki](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV9%CB%96%5D-Cache-contract).

### Removed `Couchbase` driver (SDK 2 support dropped)
It is now replaced by `Couchbasev3` driver (SDK 3), the configuration options remains the same plus `scopeName` and `collectionName` that are now configurable.

### Updated EventManager callback parameters
- Updated argument type #2 (`$items`) of `onCacheSaveMultipleItems()` event from `ExtendedCacheItemInterface[]` to `EventReferenceParameter($items)`
- Updated argument type #2 (`$items`) of `onCacheCommitItem()` event from `ExtendedCacheItemInterface[]` to `EventReferenceParameter($items)`
- Updated argument type #2 (`$value`) of `onCacheItemSet()` event from `mixed` to `EventReferenceParameter(mixed $value)`

See [EVENTS.md](./../EVENTS.md) file for more information
### Upgraded Phpfastcache API
- The Phpfastcache API has been upgraded to `4.0.0` with BC breaks. [See full changes](./../../CHANGELOG_API.md)
- Renamed `Api::getPhpFastCacheVersion()` to `Api::getPhpfastcacheVersion()`
- Renamed `Api::getPhpFastCacheChangelog()` to `Api::getPhpfastcacheChangelog()`
- Renamed `Api::getPhpFastCacheGitHeadHash()` to `Api::getPhpfastcacheGitHeadHash()`

### Removed `Devtrue` and `Devfalse` drivers
They have not been replaced.
However, the `Devrandom` driver with configurable factor chance and data length has been added

### Removed configuration entry `htaccess` for files-based drivers.
- We consider that it's no longer the task of Phpfastcache to handle server configuration
- Removed `IOConfigurationOptionTrait::getHtaccess()`
- Removed `IOConfigurationOptionTrait::setHtaccess()`

### Configuration object will now be locked once the cache pool instance is running
If you try to set a configuration value after the cache pool instance is being built, an exception will be thrown.

### Removed `Cookie` driver because of its potential dangerosity
However, you can always implement it by yourself if you want to by putting it back from previous versions using `\Phpfastcache\CacheManager::addCustomDriver()` method

------
More information in our comprehensive [changelog](./../../CHANGELOG.md).




