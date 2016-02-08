<?php
namespace phpFastCache\Core;

/**
 * Class CacheManager
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
class CacheManager
{
    protected static $instances = array();

    /**
     * @param string $storage
     * @param array $config
     * @return DriverAbstract
     */
    public static function getInstance($storage = 'auto', $config = array())
    {
        $storage = strtolower($storage);
        if (empty($config)) {
            $config = phpFastCache::$config;
        }

        if ($storage == '' || $storage == 'auto') {
            $storage = phpFastCache::getAutoClass($config);
        }

        $instance = md5(serialize($config) . $storage);
        if (!isset(self::$instances[ $instance ])) {
            $class = '\phpFastCache\Drivers\\' . $storage;
            self::$instances[ $instance ] = new $class($config);
        }

        return self::$instances[ $instance ];
    }
	/*
	 * CacheManager::Files();
	 * CacheManager::Memcached();
	 */
	public static function __callStatic($name, $arguments)
	{
		return call_user_func_array(array("self","getInstance"), array($name, $arguments));
	}
}