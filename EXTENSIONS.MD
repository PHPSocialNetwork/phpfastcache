## As of the 9.2 an extension concept has been introduced.

It's pretty but allow Phpfastcache to split up its drivers into sub-repository with a simple additional call to make.

The 9.2 make the beginning of a new external extension: `Couchbasev4`

There's only 2 steps:

1) Add it to your composer: `composer install phpfastcache/couchbasev4-extension`
2) Call the CacheManager: `CacheManager::getInstance('Couchbasev4')`

Be aware that as of the v10 the following drivers will be **MOVED** to their own sub-repositories an extension: 
- `Arangodb`
- `Couchdb`
- `Cassandra`
- `Dynamodb`
- `Firestore`
- `Mongodb`

However `Couchbasev3` will stay in the core for compatibility reason but will be **deprecated**. 
