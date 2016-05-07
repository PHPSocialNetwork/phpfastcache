<?php
namespace phpFastCache\Core;

/**
 * Trait MemcacheDriverCollisionDetectorTrait
 * @package phpFastCache\Core
 */
trait MemcacheDriverCollisionDetectorTrait
{
    /**
     * @var string
     */
    protected static $driverUsed;

    /**
     * @param $driverName
     * @return bool
     */
    public static function checkCollision($driverName)
    {
        $CONSTANT_NAME = __NAMESPACE__ . '\MEMCACHE_DRIVER_USED';

        if ($driverName && is_string($driverName)) {
            if (!defined($CONSTANT_NAME)) {
                define($CONSTANT_NAME, $driverName);

                return true;
            } else if (constant($CONSTANT_NAME) !== $driverName) {
                trigger_error('Memcache collision detected, you used both Memcache and Memcached driver in your script, this may leads to unexpected behaviours',
                  E_USER_WARNING);

                return false;
            }

            return true;
        }

        return false;
    }
}