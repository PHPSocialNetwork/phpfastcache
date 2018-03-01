## 5.0.21
##### 1 march 2018
- __Drivers__
    - Fixed #576 // Devnull driver returning non-null value

## 5.0.20
##### 30 january 2018
- __Core__
    - **Fixed #560** // Massive storage date issue for 30days+ expiration dates (@Geolim4)

## 5.0.19
##### 10 november 2017
- __Global__
    - Removed VersionEye badge :sob: (@geolim4)
    - Added VersionEye mention to credits (@geolim4)
    - Added comprehensive changelog Geolim4
    - Updated CREDITS.txt to markdown file (@geolim4)
- __Core__
    - Fixed #529 // Memory leak caused by item tags (@geolim4)
- __Drivers__
    - Fixed #518 // Memcached seems get() not working	Geolim4
- __Tests__
    - Fixed hhvm build with subprocesses (@geolim4)
    - Added test for option "itemDetailedDate" (@geolim4)

## 5.0.18
##### 16 september 2017
- Fixed #505 // 'auto' driver causes each driver to instantiate, instead of stopping after first (@Geolim4)

## 5.0.17
##### 31 july 2017
- Fixed issue on (P)Redis with negative TTLs awaiting more specification of the PSR6 about this. (@Geolim4)
- Fixed multiple item read on deleteItem (@Geolim4)
- Removed duplicated readHit attribute (@Geolim4)
- Updated v5 readme to mention v6 release (@Geolim4)
- Updated htaccess generator code to be "Apache >= 2.4" compatible (@Geolim4, @ElGigi)

## 5.0.16
##### 15 april 2017
- Fixed #450 PHP Warning on Apcu driver (@geolim4)
- Fixed #445 Memcache misconfiguration issues (@geolim4)
- Fixed mongodb driver fatal error (@jomisacu)
- Fixed #431 Clean/delete parent cache dirrectory if it empty after call deleteItem()/deleteItems() for Files driver. (@landy2005)
- Added `@property` annotation on MongoDb driver (@geolim4)

## 5.0.15
##### 04 february 2017
- **Fixed critical bug after item deletion: The item kept in memory was not reset** (@Geolim4)
- Fixed issue #423 // Couchbase key must not exceed 250 bytes (@Geolim4)
- Fixed #417 // getAbsolutePath returns relative path when in phar (@Geolim4)
- Fixed CouchBase settings array (@git-webmaster)
- Improved documentation (@westy92)
- Tests should not be tested by scrutinizer (@Geolim4)

## 5.0.14
##### 09 january 2017
- Merge pull request #416 from Geolim4
- Fixed #414
- Fixed #411
- Removed unused method encodeFilename() on DriverBaseTrait

## 5.0.13
##### 04 january 2017
- Fixed #406 Method appendItemsByTags calls the wrong internal method
- Fixed #411 SSDB + delete items by Tag
- Fixed redundant code in Predis driver
- Fixed critical vulnerability on cookie driver
- Fixed #402 Apcu driver should call apcu_\* functions (@MarcoMiltenburg)
- Removed composer "provide" section which cause HHVM build to fail
- Deprecated error is deprecated via E_USER_DEPRECATED
- Added property PhpDoc to PathSeekerTrait
- Added Github bits
- Added Drupal 8 info text in readme

## 5.0.12
##### 21 december 2016
- Added **'provide'** section in composer
- Added more strict checks for MongoDb driver
- Added PFC_IGNORE_COMPOSER_WARNING constant
- Engrish fixes on the readme (@ylorant)
- Fixed #392
- Fixed wrong class name in ExtendedCacheItemPoolInterface

## 5.0.11
##### 19 november 2016
- Added IO tests for disk-based drivers @Geolim4
- Check file existence and availability of writable @golodnyi
- Code optimization @golodnyi
- Merge pull request @Geolim4
- Code optimization @bukowskiadam
- Performance improvement for Directory::getAbsolutePath() @jfcherng
- Added anti-regression test for #373   @Geolim4
- Rewrited tests @Geolim4
- Display a notice if the project is a Symfony project and does not make use of Symfony Bundle @Geolim4

## 5.0.10
##### 19 september 2016
- Moved licence to root dir
- Fixed #357

## 5.0.9
##### 21 august 2016
- Fixed notice on Memcache driver when the server just started
- Fixed wrong namespace on Devfalse driver
- Fixed driver clear() failure on Windows...
- Fix the Sqlite driver is unable to fetch expired items (thanks @jfcherng - Jack Cherng)
- Fixed #330
- Updated readme & credits

## 5.0.8
##### 04 august 2016
- Added Drivers for Zend Data Cache (thanks to @hammermaps)
- Improved code quality as Per Scrutinizer report
- Updated TravisCi settings

## 5.0.7
##### 31 july 2016
- Updated TravisCi settings (Added hhvm + nightly)
- Updated dependencies versions
- Fixed typo in readme
- Fixed issue with Predis stats

## 5.0.6
##### 30 july 2016
- Implemented #133
- Implemented #331
- Fixed typo in README.md by @BurlesonBrad #328
- Fixed wrong method name in README.md
- Fixed and simplified the clearing of the instance array by @r0b.
- Added phpFastCacheAbstractProxy test
- Fixed bug with tags that leave residues key to tags item themselves
- Removed unused datetime check on file-based drivers. > They are already handled by the getItem()

## 5.0.5
##### 13 july 2016
- Fixed bug on tags that are not working on the first cache write
- Implemented JsonSerializable interface to ExtendedCacheItemInterface
- Fixed wrong phpDoc

## 5.0.4
##### 09 july 2016
- **Fixed critical bug with date calculation**

## 5.0.3
##### 09 july 2016
- Added missing phpDoc methods in CacheManager + increased CacheManager::setup() method life time until v6
- Merge pull request #314 from Geolim4/final
- Fixed #313
- Removed hardcoded paths

## 5.0.2
##### 04 july 2016
- Fixed standalone autoload issue
- Warn users about Mongo/MongoDB extension
- Improved tests to test the CacheItemInterface
- Updated composer.json version to be semver compliant
- Fixed wrong phpDoc comment

## 5.0.1
##### 02 july 2016
- This release includes the Psr6 interfaces for legacy users

## 5.0.0 Gold Release
##### 01 july 2016
Please see [the readme](https://github.com/PHPSocialNetwork/phpfastcache/blob/final/README.md) to see what's new in V5:

## 5.0.0-rc3
##### 26 june 2016
- Var type adjustments
- Require the JSON extension as per new JSON methods
- Fixed #294
- Fixed typo

## 5.0.0-rc2
##### 18 june 2016
- Updated Readme
- Re-implemented driver fallback
- Deprecated CacheManager::setup() in favor of CacheManager::setDefaultConfig()
- Fixed return in deprecated alias
- Re-added clean() method and deprecated it in favor of clear().
- Updating trait insertion

## 5.0.0-rc
##### 06 june 2016
- Fixed Json formatting + tags behaviour
- Updated examples headers to includes MIT licence
- Added extended JSON methods to ItemPool
- Added basic API class (See Wiki)
- Added phpFastCacheAbstractProxy (See Wiki)
- Updated roadmap && readme

## 5.0.0-beta2
##### 27 may 2016
- Removed duplicate in readme
- Ps2 compliance + added php ext-intl suggestion
- Improved Sqlite driver check
- Improved travis tests efficiency
- Updated Wincache Info driver

## 5.0.0-beta
##### 20 may 2016
- Fixed Ssdb cache clear

## 5.0.0-alpha2
##### 16 may 2016
- This release mostly make compatibility update with Phpfastcache-Bundle

## 5.0.0-alpha
##### 15 may 2016
- First V5 Pre-release









