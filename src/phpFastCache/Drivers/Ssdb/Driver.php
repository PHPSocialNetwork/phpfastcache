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

namespace phpFastCache\Drivers\Ssdb;

use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpssdb\Core\SimpleSSDB;
use phpssdb\Core\SSDBException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property SimpleSSDB $instance Instance of driver service
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

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
        } elseif (!$this->driverConnect()) {
            throw new phpFastCacheDriverException('Ssdb is not connected, cannot continue.');
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        static $driverCheck;
        if ($driverCheck === null) {
            return ($driverCheck = class_exists('phpssdb\Core\SSDB'));
        }

        return $driverCheck;
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
            return $this->instance->setx($item->getEncodedKey(), $this->encode($this->driverPreWrap($item)), $item->getTtl());
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $val = $this->instance->get($item->getEncodedKey());
        if ($val == false) {
            return null;
        } else {
            return $this->decode($val);
        }
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
            return $this->instance->del($item->getEncodedKey());
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return $this->instance->flushdb('kv');
    }

    /**
     * @return bool
     * @throws phpFastCacheDriverException
     */
    protected function driverConnect()
    {
        try {
            $server = isset($this->config[ 'ssdb' ]) ? $this->config[ 'ssdb' ] : [
              'host' => "127.0.0.1",
              'port' => 8888,
              'password' => '',
              'timeout' => 2000,
            ];

            $host = $server[ 'host' ];
            $port = isset($server[ 'port' ]) ? (int)$server[ 'port' ] : 8888;
            $password = isset($server[ 'password' ]) ? $server[ 'password' ] : '';
            $timeout = !empty($server[ 'timeout' ]) ? (int)$server[ 'timeout' ] : 2000;
            $this->instance = new SimpleSSDB($host, $port, $timeout);
            if (!empty($password)) {
                $this->instance->auth($password);
            }

            if (!$this->instance) {
                return false;
            } else {
                return true;
            }
        } catch (SSDBException $e) {
            throw new phpFastCacheDriverCheckException('Ssdb failed to connect with error: ' . $e->getMessage(), 0, $e);
        }
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
        $stat = new DriverStatistic();
        $info = $this->instance->info();

        /**
         * Data returned by Ssdb are very poorly formatted
         * using hardcoded offset of pair key-value :-(
         */
        $stat->setInfo(sprintf("Ssdb-server v%s with a total of %s call(s).\n For more information see RawData.", $info[ 2 ], $info[ 6 ]))
          ->setRawData($info)
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setSize($this->instance->dbsize());

        return $stat;
    }
}