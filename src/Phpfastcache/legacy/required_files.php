<?php
/**
 *  Compatibility for user that
 *  does not make use of composer
 */

// Core files
require_once '../core/DriverAbstract.php';
require_once '../core/DriverInterface.php';
require_once '../core/InstanceManager.php';
require_once '../core/Phpfastcache.php';
require_once '../core/PhpfastcacheExtensions.php';

// Drivers files
require_once '../drivers/apc.php';
require_once '../drivers/cookie.php';
require_once '../drivers/files.php';
require_once '../drivers/memcache.php';
require_once '../drivers/memcached.php';
require_once '../drivers/predis.php';
require_once '../drivers/redis.php';
require_once '../drivers/sqlite.php';
require_once '../drivers/ssdb.php';
require_once '../drivers/wincache.php';
require_once '../drivers/xcache.php';

// Exceptions
require_once '../exceptions/PhpfastcacheCoreException.php';
require_once '../exceptions/PhpfastcacheDriverException.php';