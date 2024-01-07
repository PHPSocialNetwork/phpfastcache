Because the V9.2 is **completely** backward compatible with the V9.1, there's a small guide to help you to migrate your code:

### :warning: Phpfastcache core drivers has been moved to their own extensions
If you were using one of those drivers:

`Arangodb`
`Couchdb`
`Dynamodb`
`Firestore`
`Mongodb`
`Solr`

Your **CODE IS SAFE AND FINE**, you just need to add a new composer dependency, ex: 
```bash
composer install phpfastcache/arangodb-extension
```

### :new: Couchbasev4 has been as an extension:
```bash
composer install phpfastcache/couchbasev4-extension
```
However `Couchbasev3` **will stay in the core** for compatibility reasons but will be deprecated.


That's it :)
------
More information in our comprehensive [changelog](./../../CHANGELOG.md).




