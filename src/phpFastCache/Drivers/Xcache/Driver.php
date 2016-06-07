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

namespace phpFastCache\Drivers\Xcache;

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
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
        return extension_loaded('xcache') && function_exists('xcache_get');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return xcache_set($item->getKey(), $this->encode($this->driverPreWrap($item)), $item->getTtl());

        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $data = $this->decode(xcache_get($item->getKey()));
        if ($data === false || $data === '') {
            return null;
        }

        return $data;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return xcache_unset($item->getKey());
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        $cnt = xcache_count(XC_TYPE_VAR);
        for ($i = 0; $i < $cnt; $i++) {
            xcache_clear_cache(XC_TYPE_VAR, $i);
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function driverConnect()
    {
        return true;
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return driverStatistic
     */
    public function getStats()
    {
        if (!ini_get('xcache.admin.enable_auth')) {
            $info = xcache_info(XC_TYPE_VAR, 0);

            return (new driverStatistic())
              ->setSize(abs($info[ 'size' ] - $info[ 'avail' ]))
              ->setData(implode(', ', array_keys($this->itemInstances)))
              ->setInfo(sprintf("Xcache v%s with following modules loaded:\n %s", XCACHE_VERSION, str_replace(' ', ', ', XCACHE_MODULES)))
              ->setRawData($info);
        } else {
            throw new \RuntimeException("PhpFastCache is not able to read Xcache configuration. Please put this to your php.ini:\n
            [xcache.admin]
            xcache.admin.enable_auth = Off\n
            Then reboot your webserver and make sure that the native Xcache ini configuration file does not override your setting.");
        }
    }
}