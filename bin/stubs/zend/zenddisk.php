<?php
/**
 * @see https://help.zend.com/zend/current/content/zendserverapi/zend_data_cache-php_api.htm
 */


/**
 * Stores a variable identified by a key into the cache.
 * If a namespace is provided, the key is stored under that namespace.
 * Identical keys can exist under different namespaces
 *
 * @param string $key
 * @param mixed  $value
 * @param int    $ttl
 *
 * @return bool
 */
function zend_disk_cache_store(string $key, mixed $value , int $ttl = 0): bool {return true;}

/**
 * Stores a variable identified by key into the memory cache.
 * If the given key is already cached, it won't be modified.
 * If a namespace is provided, the key is stored under that namespace.
 * Identical keys can exist under different namespaces.
 *
 * @param string $key
 * @param mixed  $value
 * @param int    $ttl
 *
 * @return bool
 */
function zend_disk_cache_add(string $key, mixed $value , int $ttl = 0): bool {return true;}

/**
 * Fetches data from the cache.
 * The key can be prefixed with a namespace to indicate searching within the specified namespace only.
 * If a namespace is not provided, the Data Cache searches for the key in the global namespace
 *
 * @param mixed         $key
 * @param callable|null $callback
 *
 * @return mixed
 */
function zend_disk_cache_fetch(mixed $key, ?callable $callback = null): mixed {return null;}

/**
 * Finds and deletes an entry from the cache, using a key to identify it.
 * The key can be prefixed with a namespace to indicate that the key can be deleted within that namespace only.
 * If a namespace is not provided, the Data Cache searches for the key in the global namespace
 *
 * @param mixed     $key
 * @param bool|null $clusterDelete
 *
 * @return bool
 */
function zend_disk_cache_delete(mixed $key, ?bool $clusterDelete = true): bool{return true;}

/**
 *  Deletes all entries from all namespaces in the cache,
 * if a 'namespace' is provided,
 * only the entries in that namespace are deleted
 *
 * @param string|null $namespace
 * @param bool|null   $clusterDelete
 *
 * @return bool
 */
function zend_disk_cache_clear(?string $namespace = null, ?bool $clusterDelete = true): bool{return true;}

/**
 * Provide the user information about the memory data cache
 *
 * @return array|false
 */
function zend_disk_cache_info(): array|false{return [];}
