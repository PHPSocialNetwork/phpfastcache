## 6.1.2
##### 23 march 2018
- __Core__
    - Fixed #581 // Files driver "securityKey" option configuration not working as documented

## 6.1.1
##### 1 march 2018
- __Global__
    - Added discrete Patreon badge
    - Fixed #569 // Updated wiki code sample
- __Drivers__
    - Fixed #576 // Devnull driver returning non-null value

## 6.1.0
##### 30 january 2018
- __Global__
    - Updated "path" configuration validator (Now usable for UNIX sockets and "files" drivers path) (@Geolim4)
    - Upgrading straight from 6.0.8 to 6.1.0 as per [Semver](https://semver.org/) since the UNIX socket support has been added for (P)Redis & Memcache(d). The BC stays intact.
- __Core__
    - **Fixed #560** // Massive storage date issue for 30days+ expiration dates (@Geolim4)
- __Drivers__
    - Added multiple Memcache(d) configuration style support (@Geolim4)
    - Fixed missing parameters in memcache driver (@Geolim4)
    - Fixed #563 // Memcached TTL issue (@Geolim4)
    - **Added UNIX socket support** for (P)Redis and Memcache(d) as requested in #563 (@Geolim4)
- __Tests__
    - Added new test for Memcached (@Geolim4)

## 6.0.8
##### 15 december 2017
- __Global__
    - Fixed #547 // Link to wiki page (@do-you-even-curl)
- __Core__
    - Fixed #554 // Log actual mkdir() failure reason (@Geolim4)
    - Fixed #541 // Random "key does not exist on the server" messages (@Geolim4)
- __Drivers__
    - Fixed #549 // Mongodb driver + itemDetailedDate option generates driverUnwrapCdate error (@Geolim4)
- __Helpers__
    - Fixed #545 // Psr16Adapter get item even if it is expired (@Geolim4)

## 6.0.7
##### 10 november 2017
- __Global__
    - Removed VersionEye badge :sob: (@geolim4)
    - Added VersionEye mention to credits (@geolim4)
    - Added comprehensive changelog	Geolim4
    - Added tests against php 7.2 Geolim4
    - Updated CREDITS.txt to markdown file (@geolim4)
    - Updated EVENTS.md (@geolim4)
    - Added OPTIONS.md	27/10/2017 19:42	Geolim4
- __Core__
    - Fixed #529 // Memory leak caused by item tags (@geolim4)
- __Tests__
    - Fixed hhvm build with subprocesses (@geolim4)
    - Added test for option "itemDetailedDate" (@geolim4)

## 6.0.6
##### 19 october 2017
- Added Semver badge (@Geolim4)
- Added comprehensive changelog (@Geolim4)
- Fixed #515 // Fixed inefficient Mongodb::driverCheck() method (@Geolim4)
- Fixed #517 // Couchbase error 	(@Geolim4)
- Fixed #518 // Memcached seems get() not working (@Geolim4)
- Modified credits.md file with latest updates (@Geolim4)
- Renamed CREDITS.txt to markdown file (@Geolim4)
- Updated sample file withoutComposer.php (@Abs)
- Removed useless spaces in changelog	21:02	(@Geolim4)

## 6.0.5
##### 16 september 2017
- Fixed little typo in readme  (@Geolim4)
- Psr16 do not cache with negative ttl (@kfreiman)
- Fixed #505 // 'auto' driver causes each driver to instantiate, instead of stopping after first (@Geolim4)
- Simplify CacheManager::validateConfig (@Cosmologist)

## 6.0.4
##### 31 july 2017
- Use Redis on tests instead of Predis when we can (@Geolim4)
- Fixed issue on (P)Redis with negative TTLs awaiting more specification of the PSR6 about this (@Geolim4)
- Fix @package title name of namespace (@hammermaps)
- Add Zendshm & Zenddisk to StaticSystemDrivers (@hammermaps)

## 6.0.3
##### 15 july 2017
- Added Wiki link in ISSUE_TEMPLATE.md (@Geolim4)
- Added CustomNamespaces test (@Geolim4)
- Added fetchAllKeys example as per #494 (@Geolim4)
- Fixed examples vendor path (@Geolim4)
- Fixed multiple item read on deleteItem (@Geolim4)
- Fixed type hint typo in DriverBaseTrait (@Geolim4)
- Fixed #497 // phpFastCache\Helper\Psr16Adapter::setMultiple method don't support $ttl param (@Geolim4, @tandaridaniel)
- Fixed #463 // Another possible micro-performance issue (@Geolim4)
- Fixed #463 // Possible micro-performance issue (@Geolim4)
- Updated travis ci config (@Geolim4)
- Updated setNamespace method (@Geolim4)
- Removed duplicated readHit attribute (@Geolim4)

## 6.0.2
##### 26 june 2017
- Added code of conduct  (@Geolim4)
- Added licence header to scrutinizer config file (@Geolim4)
- Added php 7.1 to travis build (@Geolim4)
- Added Predis connection error catching (@Geolim4)
- Added #468 // No clear documentation about using phpfastcache new driver(@Altegras, @Geolim4)
- Added #467 // Allow to specify the file extension in the File Driver (@rwngallego, @Geolim4)
- Fixed #462 // Markdown issue with issue template (@PerWiklander, @Geolim4)
- Fixed #471 // "Fallback must be a boolean" error (@Geolim4)
- Fixed notice in Zendshm driver (@Geolim4)
- Fixed some space/tabs/namespace issues as per psr2 specs (@Geolim4)
- Fixed type hint in cacheManager (@Geolim4)
- Fixed typo in doc (@Geolim4)
- Fixed typo in readme (@Geolim4)
- Fixed Predis type hint fix in item deletion (@Geolim4)
- Improved phpDoc blocks pertinence (@Geolim4)
- Improved global code performances to optimize huge loop operations as per #463 (@mbiebl, @Geolim4). Thanks to @mbiebl for the comparative benchmarks !!
- Removed useless code in cookie driver (@Geolim4)
- Updated Cassandra stubs (removed useless source code) (@Geolim4)
- Updated codeclimate config (@Geolim4)
- Updated scrutinizer config (@Geolim4)

## 6.0.1
##### 19 may 2017
- Fixed #460 Unknown driver path reference (@Geolim4)
- Fixed #459 examples/withoutComposer.php missing (@Geolim4)
- Fixed #456 // Bug of Exception workflow in cacheManager introduced in v6 (@Geolim4)
- Fixed ambiguous `$badPracticeOmeter` counter (@Geolim4)
- Removed root "examples" directory (@Geolim4)
- Updated migration guide from v5 to v6 (@Geolim4)

## 6.0.0
##### 11 may 2017
### Changes since 6.0.0-rc4
 - Fixed #445 regression

### Changes since v5
[Migrate your code from v5 to v6](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV5%CB%96%5D-Migrating-your-code-to-the-V6) | [Full changelog since v5](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%5D-Changelog) | [Global Support Timeline](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV4%CB%96%5D-Global-support-timeline)

## 6.0.0-rc3
##### 29 april 2017
- Added parameter type hint to Psr16Adapter constructor (@ElGigi)
- Added Help to Couchdb and Predis drivers (@Geolim4)
- Added cache conditional helper (@Geolim4)
- Added migration directory (@Geolim4)
- Fixed #445 // Setting a host/port for Memcached (@Geolim4)
- Fixed issue in mongoDb driver (@Geolim4)
- Updated htaccess generator code to be "Apache >= 2.4" compatible (@ElGigi)
- Updated Travis' dependencies installer (@Geolim4)
- Updated composer.lock (@Geolim4)
- Moved Predis/CouchDb from "required dependencies" to "suggested dependencies" (@ElGigi, @ylorant, @Geolim4)
- Normalized `phpFastCache\Entities\DriverStatistic` class name (@Geolim4)
- Enforcing `$driverName` to be a string on CacheManager (@Geolim4)
- Pulled out Couchdb dependency to our own one (@Geolim4)

## 6.0.0-rc3
##### 16 march 2017
- **Added cache slams protection (@Geolim4)**
- Added more descriptive message in exception #441 (@Geolim4)
- Replaced \InvalidArgumentException occurrences with phpFastCacheInvalidArgumentException (@Geolim4)
- Replaced \LogicException occurrences with phpFastCacheLogicException (@Geolim4)
- Added "@return static" to "setEventManager" method interface(@Geolim4)

## 6.0.0-rc2
##### 14 february 2017
- **Added Couchdb driver, yay !**   (@Geolim4)
- **Added configuration validator** (@Geolim4)
- Added custom key hash function (@Geolim4)
- Updated Mongodb driver to use Mongodb driver instead of Mongo class. (@Geolim4)
- Updated composer.lock (@Geolim4)
- Updated API version to 1.2.5 (@Geolim4)
- Updated private methods to protected (@Geolim4)
- Updated method name: setChmodAuto => getDefaultChmod (@Geolim4)
- Updated documentation (@westy92)
- Fixed critical bug after item deletion: The item kept in memory was not reset. (@Geolim4)
- Fixed php compile error   (@Geolim4)
- Fixed Clean/delete parent cache directory if it empty after call deleteItem()/deleteItems() (@landy2005 )
- Fixed issue #423 // Couchbase key must not exceed 250 bytes (@Geolim4)
- Fixed issue #425 // Devfalse or Devtrue driver not working (@Geolim4)
- Fixed CouchBase settings array (@git-webmaster)
- Removed unused index.html (@Geolim4)

## 6.0.0-rc1
##### 25 january 2017
- **Added Cassandra Driver**
- **Added Psr16 support, yay !**
- Added getHelp() method to provides basic help about a specific driver
- Added missing copyright header + Fixed PhpDoc comment
- Removed deprecated ArrayAccess compatibility for driverStatistic entity
- Removed deprecated method IOHelperTrait::isExpired()
- Removed clear() method as planned, replaced by clean()
- Updated double quotes to be safely replaced by simple quotes
- Updated composer to add Cassandra mentions
- Updated readme & docs for Cassandra driver
- Updatred tests to not be tested by scrutinizer  
- Updated \InvalidArgumentException to \phpFastCache\Exceptions\phpFastCacheInvalidArgumentException
- Removed unused imports + LevelDb Stub hint fix
- Removed extra argument in tests
- Removed extra new line
- Added Unsupported key characters specifications
- Fixed mistakes on readme.md
- Fixed #417
- Fixed #414
- Fixed method call case on predis driver

## 6.0.0-beta2
##### 4 january 2017
- Fixed #411
- Fixed #406
- Fixed redundant code in Predis driver
- Fixed Interface typos
- Fixed critical vulnerability on cookie driver
- Fixed broken build on Unix Env
- Fixed type on ActOnAll interface
- Fixed redundant code in CouchBase driver
- Fixed loosely compared booleans
- Fixed typos in ExtendedCacheItemPoolInterface 
- Fixed Apcu driver should call apcu_\* functions (@MarcoMiltenburg)
- Fixed Typo in exception
- Implemented #404 
- Added PhpDoc to TestHelper constructor
- Added TestHelper class to make tests much cleaner
- Added Github bits
- Added Drupal 8 info text in readme
- Added property PhpDoc to IOHelperTrait
- Moved "examples" directory inside "docs" directory
- Moved Lexer test to a distinct directory
- Removed deprecated method as planned in V6
- Removed unused class imports
- Removed composer "provide" section which cause HHVM build to fail :/
- Removed unused extract() in tests
- Updated travis configuration
- Updated IOHelperTrait will now provides a generic getStats() method
- Updated Composer now requires mb_string extension
- Clarifying external properties in IOHelperTrait

## 6.0.0-beta1
##### 21 december 2016
- Engrish fixes on the readme (@ylorant)
- Added Memstatic driver
- Added **'provide'** section in composer
- Added more strict checks for MongoDb driver
- Added PFC_IGNORE_COMPOSER_WARNING constant
- Fixed #392
- Fixed wrong class name in ExtendedCacheItemPoolInterface
- Update README.md
- Added IO tests for disk-based drivers
- Code optimization(@jfcherng, @golodnyi, @bukowskiadam)
- Added events description on Wiki
- Added anti-regression test for #373

## 6.0.0-alpha2
##### 12 november 2016
- Fixed #373 > Files driver issue after clearing cache
- Added anti-regression test for #373
- Added static config entry **autoTmpFallback** via #373
- Rewrited tests for more efficiency
- Fixed #375 >Fatal error on Predis
- Credited Sabine van Lommen from Zend.com to Hall of Fame
- Added phpFastCache\Exceptions\phpFastCacheIOException
- Fixed #366 > Wrong use of realpath() on non existing path
- Added static config entry **secureFileManipulation** via #366
- Displays a notice if the project is a Symfony project and does not make use of Symfony Bundle
- Moved licence to root dir

## 6.0.0-alpha
##### 19 september 2016
### Highlight features
- Added EventManager *
- ActOnAll Helper
- Added Drivers for Zend Data Cache
- Added phpFastCacheAbstractProxy
- Added creation/modification date for Items (Requires the conf entry "_itemDetailedDate_" to be enabled)

### BC Break **
- Removed Item::Touch() method which is now deprecated
- Changed Pool/Item classes in a different directory
- The V6 now requires php 5.6 instead of php 5.5 for the V5
- Implemented JsonSerializable interface to ExtendedCacheItemInterface

### Fixes
- Moved licence to root dir
- Fixed broken build. Whoops !
- Updated Readme to add new API methods
- Added test for EventManager
- Added [FAIL] tag to ActOnAll test
- Removing Roadmap, the Github project feature now do the job
- Added ActOnAll tests
- Better using Reflection that call_user_*
- V5.1 is becoming V6
- Fixed #357
- Fixed notice on Memcache driver when the server just started
- Fixed wrong namespace on Devfalse driver
- Fixed driver clear() failure on Windows...
- Fix the Sqlite driver is unable to fetch expired items (Jack Cherng)
- Fix $this->namespaces to $this->itemInstances (Lucas)
- Update the getStats() function for zend memory cache (Lucas)
- Improved code quality
- Trying to fix travis build with hhvm
- Updated TravisCi settings
- Updated dependencies versions
- Implemented #133
- Implemented #331
- Fixed and simplified the clearing of the instance array. @r0b
- Merge pull request #321 from Geolim4/final
- Fixed wrong test path file
- Added phpFastCacheAbstractProxy test
- Fixed bug with tags that leave residues key to tags item themselves
- Removed unused datetime check on file-based drivers. > They are already handled by the getItem()
- Fixed bug on tags that are not working on the first cache write
- Fixed critical bug with date calculation
- Update README.md
- Added missing phpDoc methods in CacheManager + increased CacheManager::setup() method life time
- Fixed #313

### Tips
- * [Introducing to events](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%5D-Introducing-to-events)
- ** [Migrating your code from the V5 to the V6](https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV5%5D-Migrating-your-code-to-the-V6)
