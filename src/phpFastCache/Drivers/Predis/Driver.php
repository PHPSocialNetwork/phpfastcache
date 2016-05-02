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

namespace phpFastCache\Drivers\Predis;

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;
use Predis\Client as PredisClient;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    use StandardPsr6StructureTrait;

    /**
     * @var PredisClient Instance of driver service
     */
    public $instance;

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
        }else{
            $this->driverConnect();
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        if(extension_loaded('Redis')){
            trigger_error('The native Redis extension is installed, you should use Redis instead of Predis to increase performances', E_USER_NOTICE);
        }
        return class_exists('Predis\Client');
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
            $ttl = $item->getExpirationDate()->getTimestamp() - time();

            return $this->instance->setex($item->getKey(), $ttl, $this->encode($this->driverPreWrap($item)));
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public function driverRead($key)
    {
        $x = $this->instance->get($key);
        if ($x == false) {
            return null;
        } else {
            return $this->decode($x);
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
            return $this->instance->del($item->getKey());
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    public function driverClear()
    {
        return $this->instance->flushDB();
    }

    /**
     * @return bool
     */
    public function driverConnect()
    {
        $server = isset($this->config[ 'redis' ]) ? $this->config[ 'redis' ] : [
          'host' => '127.0.0.1',
          'port' => '6379',
          'password' => '',
          'database' => '',
        ];

        $config = [
          'host' => $server[ 'host' ],
        ];

        $port = isset($server[ 'port' ]) ? $server[ 'port' ] : '';
        if ($port != '') {
            $config[ 'port' ] = $port;
        }

        $password = isset($server[ 'password' ]) ? $server[ 'password' ] : '';
        if ($password != '') {
            $config[ 'password' ] = $password;
        }

        $database = isset($server[ 'database' ]) ? $server[ 'database' ] : '';
        if ($database != '') {
            $config[ 'database' ] = $database;
        }

        $timeout = isset($server[ 'timeout' ]) ? $server[ 'timeout' ] : '';
        if ($timeout != '') {
            $config[ 'timeout' ] = $timeout;
        }

        $read_write_timeout = isset($server[ 'read_write_timeout' ]) ? $server[ 'read_write_timeout' ] : '';
        if ($read_write_timeout != '') {
            $config[ 'read_write_timeout' ] = $read_write_timeout;
        }

        $this->instance = new PredisClient($config);

        return true;
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
            return $this->instance->exists($item->getKey()) !== null;
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
     * @return driverStatistic
     */
    public function getStats()
    {
        return (new driverStatistic())->setInfo(implode('<br />', (array) $this->instance->info()));
    }
}