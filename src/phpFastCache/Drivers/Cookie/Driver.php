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

use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

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
            throw new phpFastCacheDriverCheckException(sprintf(self::DRIVER_CHECK_FAILURE, $this->getDriverName()));
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
    protected function driverConnect()
    {
        return !(!array_key_exists('phpFastCache', $_COOKIE) && !@setcookie('phpFastCache', 1, 10));
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $this->driverConnect();
            $keyword = self::PREFIX . $item->getKey();
            $v = json_encode($this->driverPreWrap($item));

            if (isset($this->config[ 'limited_memory_each_object' ]) && strlen($v) > $this->config[ 'limited_memory_each_object' ]) {
                return false;
            }

            return setcookie($keyword, $v, $item->getExpirationDate()->getTimestamp(), '/');
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverException
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $this->driverConnect();
        $keyword = self::PREFIX . $item->getKey();
        $x = isset($_COOKIE[ $keyword ]) ? json_decode($_COOKIE[ $keyword ], true) : false;

        if ($x == false) {
            return null;
        } else {
            if (!is_scalar($this->driverUnwrapData($x)) && !is_null($this->driverUnwrapData($x))) {
                throw new phpFastCacheDriverException('Hacking attempt: The decoding returned a non-scalar value, Cookie driver does not allow this.');
            }

            return $x;
        }
    }

    /**
     * @param string $key
     * @return int
     */
    protected function driverReadExpirationDate($key)
    {
        $this->driverConnect();
        $keyword = self::PREFIX . $key;
        $x = isset($_COOKIE[ $keyword ]) ? $this->decode(json_decode($_COOKIE[ $keyword ])->t) : false;

        return $x ? $x - time() : $x;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item)
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
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        $return = null;
        $this->driverConnect();
        foreach ($_COOKIE as $keyword => $value) {
            if (strpos($keyword, self::PREFIX) !== false) {
                $_COOKIE[ $keyword ] = null;
                $result = @setcookie($keyword, null, -10);
                if ($return !== false) {
                    $return = $result;
                }
            }
        }

        return $return;
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return DriverStatistic
     */
    public function getStats()
    {
        $size = 0;
        $stat = new DriverStatistic();
        $stat->setData($_COOKIE);

        /**
         * Only count PFC Cookie
         */
        foreach ($_COOKIE as $key => $value) {
            if (strpos($key, self::PREFIX) === 0) {
                $size += strlen($value);
            }
        }

        $stat->setSize($size);

        return $stat;
    }
}