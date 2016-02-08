<?php
namespace phpFastCache;
use phpFastCache\Core\phpFastCache;
use phpFastCache\Core\DriverAbstract;

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
        if(isset(phpFastCache::$config['overwrite'])
            && phpFastCache::$config['overwrite'] !== ''
            && phpFastCache::$config['overwrite'] !== 'auto')
        {
            phpFastCache::$config['storage'] = phpFastCache::$config['overwrite'];
            $storage = phpFastCache::$config['overwrite'];
        }
        else if(isset(phpFastCache::$config['storage'])
            && phpFastCache::$config['storage'] !== ''
            && phpFastCache::$config['storage'] !== 'auto')
        {
            $storage = phpFastCache::$config['storage'];
        }
        else if ($storage == '' || $storage == 'auto') {
            $storage = phpFastCache::getAutoClass($config);
        }

        $instance = md5(serialize($config) . $storage);
        if (!isset(self::$instances[ $instance ])) {
            $class = '\phpFastCache\Drivers\\' . $storage;
            self::$instances[ $instance ] = new $class($config);
        }

        return self::$instances[ $instance ];
    }
	/**
	 * CacheManager::Files();
	 * CacheManager::Memcached();
	 */
	public static function __callStatic($name, $arguments)
	{
        if(count($arguments) === 1 && isset($arguments[0])) {
            $arguments = $arguments[0];
        }
        switch(strtolower($name)) {
            case "setup":
                phpFastCache::setup($arguments);
                break;
            default:
                return call_user_func_array(array("self","getInstance"), array($name, $arguments));
                break;
        }
	}

}