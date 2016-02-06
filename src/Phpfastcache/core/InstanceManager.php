<?php
namespace Phpfastcache\core;

/**
 * Class InstanceManager
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */
class InstanceManager
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
            $config = Phpfastcache::$config;
        }

        if ($storage == '' || $storage == 'auto') {
            $storage = Phpfastcache::getAutoClass($config);
        }

        $instance = md5(serialize($config) . $storage);
        if (!isset(self::$instances[ $instance ])) {
            $class = '\Phpfastcache\drivers\\' . $storage;
            self::$instances[ $instance ] = new $class($config);
        }

        return self::$instances[ $instance ];
    }
}