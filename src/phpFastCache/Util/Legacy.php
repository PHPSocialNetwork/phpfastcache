<?php
    /*
     * If Any problem with Autoload on other project
     * Try to put this line on your config project
     * define("PHPFASTCACHE_LEGACY",true);
     * and just keep include phpFastCache/phpFastCache.php or Composer Autoloader
     */

    use phpFastCache\CacheManager;

    require_once __DIR__.'/../Core/DriverInterface.php';
    require_once __DIR__.'/../Core/DriverAbstract.php';
    require_once __DIR__.'/../Core/phpFastCache.php';
    require_once __DIR__.'/../Core/phpFastCacheExtensions.php';
    require_once __DIR__.'/../Exceptions/phpFastCacheCoreException.php';
    require_once __DIR__.'/../Exceptions/phpFastCacheDriverException.php';

    require_once __DIR__.'/../Drivers/files.php';
    require_once __DIR__.'/../Drivers/memcache.php';
    require_once __DIR__.'/../Drivers/memcached.php';
    require_once __DIR__.'/../Drivers/mongodb.php';
    require_once __DIR__.'/../Drivers/predis.php';
    require_once __DIR__.'/../Drivers/redis.php';
    require_once __DIR__.'/../Drivers/sqlite.php';

    require_once __DIR__.'/../CacheManager.php';
    require_once __DIR__.'/../phpFastCache.php';


    /**
     * __c() Short alias
     * @param string $storage
     * @param array $config
     * @return mixed
     */
    if (!function_exists("__c")) {
        function __c($storage = 'auto', $config = array())
        {
            return CacheManager::getInstance($storage, $config);
        }
    }


