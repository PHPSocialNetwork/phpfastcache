<?php
/**
 *  Compatibility for user that
 *  does not make use of composer
 */

// Core files
require_once __DIR__ . '/../core/DriverAbstract.php';
require_once __DIR__ . '/../core/DriverInterface.php';
require_once __DIR__ . '/../core/InstanceManager.php';
require_once __DIR__ . '/../core/Phpfastcache.php';
require_once __DIR__ . '/../core/PhpfastcacheExtensions.php';

// Drivers files
require_once __DIR__ . '/../drivers/apc.php';
require_once __DIR__ . '/../drivers/cookie.php';
require_once __DIR__ . '/../drivers/files.php';
require_once __DIR__ . '/../drivers/memcache.php';
require_once __DIR__ . '/../drivers/memcached.php';
require_once __DIR__ . '/../drivers/predis.php';
require_once __DIR__ . '/../drivers/redis.php';
require_once __DIR__ . '/../drivers/sqlite.php';
require_once __DIR__ . '/../drivers/ssdb.php';
require_once __DIR__ . '/../drivers/wincache.php';
require_once __DIR__ . '/../drivers/xcache.php';

// Exceptions
require_once __DIR__ . '/../exceptions/PhpfastcacheCoreException.php';
require_once __DIR__ . '/../exceptions/PhpfastcacheDriverException.php';