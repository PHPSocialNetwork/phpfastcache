<?php
/**
 *  Compatibility for user that
 *  does not make use of composer
 */

// Core files
require_once __DIR__ . '/../core/DriverAbstract.php';
require_once __DIR__ . '/../core/DriverInterface.php';
require_once __DIR__ . '/../core/CacheManager.php';
require_once __DIR__ . '/../core/phpFastCache.php';
require_once __DIR__ . '/../core/phpFastCacheExtensions.php';

// Drivers files
require_once __DIR__ . '/../Drivers/apc.php';
require_once __DIR__ . '/../Drivers/cookie.php';
require_once __DIR__ . '/../Drivers/files.php';
require_once __DIR__ . '/../Drivers/memcache.php';
require_once __DIR__ . '/../Drivers/memcached.php';
require_once __DIR__ . '/../Drivers/predis.php';
require_once __DIR__ . '/../Drivers/redis.php';
require_once __DIR__ . '/../Drivers/sqlite.php';
require_once __DIR__ . '/../Drivers/ssdb.php';
require_once __DIR__ . '/../Drivers/wincache.php';
require_once __DIR__ . '/../Drivers/xcache.php';

// Exceptions
require_once __DIR__ . '/../Exceptions/phpFastCacheCoreException.php';
require_once __DIR__ . '/../Exceptions/phpFastCacheDriverException.php';