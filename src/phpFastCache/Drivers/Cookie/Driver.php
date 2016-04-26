<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace phpFastCache\Drivers\Cookie;

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    use StandardPsr6StructureTrait;
    const PREFIX = 'PFC_';

    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct(array $config = [])
    {
        $this->setup($config);

        if (!$this->driverCheck()) {
            throw new phpFastCacheDriverException(sprintf(self::DRIVER_CHECK_FAILURE, 'Cookie'));
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        if (function_exists('setcookie')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function driverConnect()
    {
        return !(!array_key_exists('phpFastCache', $_COOKIE) && !@setcookie('phpFastCache', 1, 10));
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $this->driverConnect();
            $keyword = self::PREFIX . $item->getKey();
            $v = $this->encode($item->get());

            if (isset($this->config[ 'limited_memory_each_object' ]) && strlen($v) > $this->config[ 'limited_memory_each_object' ]) {
                return false;
            }

            return setcookie($keyword, $v, $item->getExpirationDate()->getTimestamp(), '/');
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $key
     * @return bool|mixed|null
     */
    public function driverRead($key)
    {
        $this->driverConnect();
        // return null if no caching
        // return value if in caching
        $keyword = self::PREFIX . $key;
        $x = isset($_COOKIE[ $keyword ]) ? $this->decode($_COOKIE[ $keyword ]) : false;
        if ($x == false) {
            return null;
        } else {
            return $x;
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function driverDelete(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $this->driverConnect();
            $keyword = self::PREFIX . $item->getKey();
            $_COOKIE[ $keyword ] = null;

            return @setcookie($keyword, null, -10);
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    public function driverClear()
    {
        $return = null;
        $this->driverConnect();
        foreach ($_COOKIE as $keyword => $value) {
            if (strpos($keyword, self::PREFIX) !== false) {
                $_COOKIE[ $keyword ] = null;
                $result = @setcookie($keyword, null, -10);
                if($return !== false){
                    $return = $result;
                }
            }
        }
        return $return;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function driverIsHit(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return array_key_exists($item->getKey(), $_COOKIE);
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return array
     */
    public function getStats()
    {
        $this->driverConnect();
        $res = [
          'info' => '',
          'size' => '',
          'data' => $_COOKIE,
        ];

        return $res;
    }
}