## 7.1.1
#### _"Rust-out"_
##### 24 april 2020
- __Drivers__
    - Fixed #716 // TypeError in lib/Phpfastcache/Drivers/Cookie/Driver.php (@motikan2010)
    - Fixed #733 // Removing path check in Redis driver before auth. (@gillytech)
- __Helpers__
    - Fixed #717 // Deprecate "ActOnAll" to remove it in v8 (@geolim4)
- __Misc__
    - Added v8 mention in Readme (@geolim4)
    - Added Github Funding option (@geolim4)

## 7.1.0
#### _"Wake the rust"_
##### 15 september 2019
- __Drivers__
    - Fixed #692 // MongoDB driver DNS seedlist format (@Geolim4)
    - Fixed #679 // TLS connexion for (P)Redis (@Geolim4)
    - Fixed #689 // (P)redis persistent / pooled connections (@Geolim4)
    - Fixed #680 // APC driverClear fails (@Geolim4)
    - Fixed #699 // Fatal type error with ssdb-server >1.9.7  (@dmz86)
    - Fixed #694 //  Files driver rare bug (@Geolim4)
- __Helpers__
    - Fixed #700 // Psr16Adapter::deleteMultiple/getMultiple converts $keys to an array (@bylexus)
    - Fixed #685 // Minor bug - fatal error in psr16 adapter if an exception is thrown (@MaximilianKresse)
- __Global__

- __Misc__
    - Updated "stale" policy (@geolim4)
    - Added Security Policy (@geolim4)
    - Fixed #695 // Typo in docs/examples/files.php (@hiroin)

## 7.0.5
#### _"Rusted"_
##### 3 march 2019
- __Drivers__
    - Fixed #675 // Memcached ignores custom host/port configurations (@Geolim4)
- __Global__
    - Fix composer package name should be all lowercase (@jfcherng)
- __Misc__
    - Updated Mongodb\Config docs (@mikepsinn)
    - Fixed "Files" example in docs (@hriziya)

## 7.0.4
#### _"Rust-in"_
##### 22 december 2018
- __Core__
    - Moved exclusive files-related configurations keys to IOConfigurationTrait (@Geolim4)
    - Added CacheManager::clearInstance() method (@Geolim4)
- __Drivers__
    - Adds drivers options parameter when building Mongo DB client (@vainj)
- __Misc__
    - Fixes PHPdoc issues (@vainj)

## 7.0.3
#### _"Rust is part of beauty after all !"_
##### 5 september 2018
- __Core__
    - Fixed wrong copyright annotation in some file headers (@Geolim4)
    - Added `PhpfastcacheDriverConnectException` exception in case that a driver failed to connect (@Geolim4)
- __Drivers__
    - Added missing option "timeout" for `Predis` oO (@Geolim4)
    - Settings up default `Couchbase` port to 8091 (@Geolim4)
    - Fixed #647 // Unwanted echo in exception (@Geolim4)
- __Misc__
    - Fixed #637 // Corrupted badges on Github readme pages

## 7.0.2
#### _"Rust is getting some gold !"_
##### 23 july 2018
- __Core__
    - Added more Opcache/Type hint optimizations (@Geolim4)
- __Drivers__
    - Implemented #627 // Redis/Predis "prefix" option (@Geolim4)
    - Fixed bug in Apcu driver with abstract getStats() method (@afdaniele)
    - Added Mongodb cluster support and uri options (@UnRyongPark)
- __Misc__
    - Updated readme as we are sure that Apc(u) is ported to php7 (@Geolim4)
    - Described clear() abstract method (@Geolim4)
    - Added new option to PULL_REQUEST_TEMPLATE.md (@Geolim4)
    - Fixed #620 // migration guide for v7 uses CamelCase in example (@Geolim4)

## 7.0.1
#### _"Gold is getting some rust !"_
##### 22 june 2018
- __Global__
    - Updated php constraint to be more reliable over the years (@Geolim4)
- __Core__
    - Fixed possible notice in some contexts (@Geolim4)
- __Drivers__
    - IOException in `Files` driver: Preventive hotfix for #614, thanks to @hriziya (@Geolim4)
- __Misc__
    - Fixed typo in README & code sample mistakes (@Geolim4)
    - Updated examples as per #612 (@Geolim4)
    - Fixed wrong code in template issues (@Geolim4)
    - Updated Github bytes (@Geolim4)
    - Fixing broken badges (@Geolim4)

## 7.0.0
#### _"We found gold !"_
##### 27 may 2018
- __Global__
    - Finally achieved 8 months of development \o/ (@Geolim4)
    - Applied one last code refactoring to enforce PSR2 compliance (@Geolim4)
    - Fixed some mismatching annotations type hints (@Geolim4)
    - Applied many Scrutinizer code style/optimisation/typo recommendations (@Geolim4)
- __Core__
    - Updated some left functions/constants namespaces that were still not absolute (@Geolim4)
    - Autoload: Isolated _PFC\_*_ constant in their own namespace to not pollute php's root namespace (@Geolim4)
    - Added new exception interface `Phpfastcache\Exceptions\PhpfastcacheExceptionInterface` that will handle all Phpfastcache-related exceptions (@Geolim4)
    - Updated CacheManager code by splitting some portions of code into different methods (@Geolim4)
- __Drivers__
    - Upgraded Couchbase to PHP SDK 2.4 as per #599 (@git-webmaster, @Geolim4)
    - Added Couchbase SDK update notice to migration guide (@Geolim4)
- __Tests__
    - Updated TestHelper to better works in Web context (if any) (@Geolim4)
    - Force exit code 0 when getting an uncaught exception PhpfastcacheDriverCheckException (@Geolim4)
    - Fix test helper handling PhpfastcacheDriverCheckException with HHVM (@Geolim4)
- __Misc__
    - Added Anton (@git-webmaster) to our "hall of fame" (@Geolim4)
    - Added "ext-couchbase" suggestion
    - Updated every annotations/comments/documentation links from HTTP to HTTPS (if available) (@Geolim4)

## 7.0.0-rc4
##### 15 may 2018
- __Core__
    - Added "defaultFileNameHashFunction" option (@Geolim4)
- __Drivers__
    - Implemented #598 // Ability to use custom predis/redis client (@Geolim4, @dol)
- __Tests__
    - Added test for custom predis client (@Geolim4)
    - Added test for custom redis client (@Geolim4)
- __Misc__
    - Added doc for redis and predis client options (@Geolim4)

## 7.0.0-rc3
##### 9 may 2018
- __Core__
    - Added method "getConfigClass" ExtendedCacheItemPoolInterface (@Geolim4)
    - Upgraded API version from 2.0.3 to 2.0.4 (@Geolim4)
    - Added "FQCNAsKey" parameter to CacheManager::getDriverList() (@Geolim4)
    - Fixed fallback behavior in getPhpFastCacheVersion method (@Geolim4)

## 7.0.0-rc2
##### 8 may 2018
- __Global__
    - More Opcache optimizations (@Geolim4)
- __Core__
    - Updated EventManager now MUST implement `Phpfastcache\Event\EventInterface` (@Geolim4)
    - Upgraded API version from 2.0.2 to 2.0.3 (@Geolim4)
    - Fixed namespace issue on EventManager (@Cyperghost)
- __Helpers__
    - Improved TestHelper efficiency (@Geolim4)
    - Forced return type hint to Psr16Adapter && added a getter for the internal cache instance (@Geolim4)
    - The Psr16Adapter will now also accept an `ExtendedCacheItemPoolInterface` object to the $driver parameter in constructor (@Geolim4)
- __Misc__
    - Updated Readme (@Geolim4)

## 7.0.0-rc
##### 8 april 2018
- __Global__
    - **Added "custom driver" and "override core driver" features** (@Geolim4)
    - **Updated & completely reworked Mongodb driver** (@ylorant)
    - Deprecated custom namespace feature in favor of the new feature above (@Geolim4)
    - Deprecated `$this->getConfigOption($optionName)` for `$this->geConfig()->getOptionName()` (@Geolim4)
    - Deprecated `$this->getConfig()->getOption($optionName)` for `$this->geConfig()->getOptionName()` (@Geolim4)
- __Core__
    - Enforced more argument type hint & absolute core php function namespaces (@Geolim4)
    - Removed `ExtendedCacheItemInterface::getUncommittedData()` that should have been removed before :| (@Geolim4)
    - Added additional atomic methods in ExtendedCacheItemInterface (`isNull()`, `isEmpty()`, `getLength()`) (@Geolim4)
- __Drivers__
    - Improved "Auto" driver context with tests, new interface method and additional checks (@Geolim4)
- __Helpers__
    - Added "NOTE" method to testHelper (@Geolim4)
- __Utils__
    - Removed unused Util "Languages" (@Geolim4)
- __Tests__
    - Fixed randomly failing test "Github-560" (@Geolim4)
    - Fixed include issue in tests (@Geolim4)
    - Updated tests for custom drivers (@Geolim4)
- __Misc__
    - Added specific IoConfiguration for files-based drivers (@Geolim4)
    - Updated README to re-arrange the API section more properly (@Geolim4)
    - Fixed HTTP images that broke site SSL seal in README (@Geolim4)
    - Updated composer description & dependencies constraints (@Geolim4)

## 7.0.0-beta3
##### 17 march 2018
- __Global__
    - **Updated root namespace: "phpFastCache\" => "Phpfastcache\"** (@Geolim4)
    - **Updated root directory: "src" => "lib"** (@Geolim4)
    - Dismissed unneeded/inconsistent "else" statements (@Geolim4)
- __Core__
    - **Capitalized Phpfastcache classe names** (@Geolim4) Please read carefully the migration guide (MigratingFromV6ToV7.md).
    - Added CacheManager::getDriverList() (@Geolim4)
    - Updated strictly return type hints in CacheManager (@Geolim4)
    - Deprecated CacheManager::getStaticAllDrivers() (@Geolim4)
    - Deprecated CacheManager::getStaticSystemDrivers() (@Geolim4)
    - Deprecated configuration option "ignoreSymfonyNotice" (@Geolim4)
    - Added PhpfastcacheUnsupportedOperationException exception (@Geolim4)
- __Drivers__
    - Fixed #576 // Devnull driver returning non-dull data (@Geolim4)
    - Fixed #581 // Files driver "securityKey" option configuration not working as documented
- __Configuration__
    - Added configuration option `fallbackConfig` for a better fallback configuration (@Geolim4)
    - Deprecated configuration option "ignoreSymfonyNotice" (@Geolim4)
- __Helpers__
    - Added exception catcher to test Helper to FAIL or SKIP depending the exception (@Geolim4)
    - Added notice/warning/error catcher to test Helper to keep a clean build report (@Geolim4)
- __Utils__
    - Added exception catcher to to test Helper to FAIL or SKIP depending the exception (@Geolim4)
    - Updated strictly return type hints in Directory class (@Geolim4)
- __Tests__
    - Added duration time for each tests (@Geolim4)
    - Updated Lexer Test for better compatibility with HHVM (@Geolim4)
    - Fixed #581 // Issue with test file namespace imports (@Geolim4)
    - Updated Travis build to include 7.2 (@Geolim4)
    - Updated scrutinizer build settings (@Geolim4)
- __Misc__
    - Added deprecation section in migrating guide
    - Moved API changelog to a standalone file
    - Professionalized a bit more the README
    - Removed lib/Phpfastcache/.htaccess that does no longer belong in its place
    - Added .gitattributes file

## 7.0.0-beta2
##### 4 march 2018
- __Core__
    - **Added new ConfigurationOption object syntax** (@Geolim4). Please read carefully the migration guide (MigratingFromV6ToV7.md).
- __Drivers__
    - Fixed #576 // Devnull driver returning non-dull data (@Geolim4)
- __Helpers__
    - Added exception catcher to to test Helper to FAIL or SKIP depending the exception (@Geolim4)
- __Tests__
    - Fixed nightly build that sometimes fails with Memcache

## 7.0.0-beta
##### 30 january 2018
- __Global__
    - Added Opcache improvements by namespacing php core's functions (@Geolim4)
    - Updated contributing.md + added coding guideline (@Geolim4)
    - Fixed little notice (@Geolim4)
- __Drivers__
    - Added UNIX socket support for (P)Redis and Memcache(d) as requested in #563 (@Geolim4)
- __Helpers__
    - Updated test helper and API to add the git version (if available) (@Geolim4)
- __Tests__
    - Fixed #560 // Potential delay issue in test (@Geolim4)

## 7.0.0-alpha3
##### 15 december 2017
- __Global__
    - Fixed #541 // Random "key does not exist on the server" messages (@Geolim4)
- __Core__
    - Fixed #554 // Log actual mkdir() failure reason (@Geolim4)
- __Drivers__
    - Fixed #549 // Mongodb driver + itemDetailedDate option generates driverUnwrapCdate error (Using V7 getOption API) (@Geolim4)
    - Fixed #548 // Wrong type hint on redis driver (@Geolim4)
- __Helpers__
    - Fixed #545 // Psr16Adapter get item even if it is expired (@Geolim4)
    - Added CacheConditionalHelper TTL (@Geolim4)
- __Tests__
    - Fixed some missing text output on HHVM builds (@Geolim4)
    - Fixed HHVM builds (@Geolim4)

## 7.0.0-alpha2
##### 10 november 2017
- __Global__
    - Fixed some typo on CREDITS.md (@geolim4)
    - Added Strict types support (@geolim4)
    - Added grouped namespaces (@geolim4)
    - Added VersionEye mention to credits (@geolim4)
    - Added OPTIONS.md (@geolim4)
    - Removed VersionEye badge :sob: (@geolim4)
    - Improved our fabulous API class to add the changelog/PhpFastCache version support (@geolim4)
    - Updated readme to add php7 strict types mention (@geolim4)
    - Updated EVENTS.md @geolim4)
    - Update withoutComposer.php (@Abs)
    - Updated CREDITS.txt to markdown file (@geolim4)
- __Core__
    - Fixed #529 // Memory leak caused by item tags (@geolim4)
    - Fixed missing sprintf in CacheManager + Added Riak method annotation (@geolim4)
    - Updated API version (@geolim4)
    - Updated composer files (@geolim4)
- __Drivers__
    - Fixed #517 // Couchbase error (@geolim4)
    - Fixed Riak dependency (@geolim4)
- __Tests__
    - Fixed hhvm build with subprocesses (@geolim4)
    - Added test for option "itemDetailedDate" (@geolim4)

## 7.0.0-alpha
##### 18 october 2017
- __Global__
  - Added php7 type hint support. Strict type support is coming in next alphas
  - Added changelog as requested by @rdecourtney in #517
  - Added `phpFastCacheInstanceNotFoundException` exception
- __Drivers__
  - Added Riak driver
  - Fixed wrong type hint returned by Predis
- __Cache Manager__
  - Added custom Instance ID feature as requested in #477
- __Helpers__
  - Modified ActOnAll helper behavior, this helper now returns an array of driver returns and does no longer implements `ExtendedCacheItemPoolInterface`

## 7.0.0-dev
##### 01 october 2017
- Initialized v7 development