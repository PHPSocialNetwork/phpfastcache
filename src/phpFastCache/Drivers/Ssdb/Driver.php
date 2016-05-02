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

use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpssdb\Core\SimpleSSDB;
use phpssdb\Core\SSDB;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    use StandardPsr6StructureTrait;
    
    /**
     * @var SimpleSSDB
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
        }elseif(!$this->driverConnect()){
            throw new phpFastCacheDriverException('Ssdb is not connected, cannot continue.');
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        static $driverCheck;
        if($driverCheck === null){
            return ($driverCheck = class_exists('phpssdb\Core\SSDB'));
        }
        return $driverCheck;
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
/*            if (isset($this->config[ 'skipExisting' ]) && $this->config[ 'skipExisting' ] == true) {
                $x = $this->instance->get($item->getKey());
                if ($x === false) {
                    return false;
                } elseif (!is_null($x)) {
                    return true;
                }
            }*/

            return $this->instance->setx($item->getKey(), $this->encode($this->driverPreWrap($item)), $item->getTtl());
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
            return $this->instance->del($item->get());
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
        foreach ($this->instance->keys('', '') as $key) {
            $result = $this->instance->del($key);
            if($result !== false){
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * @return bool
     */
    public function driverConnect()
    {
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
            return $this->instance->exists($item->get());
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
        $stat = new driverStatistic();

        $stat->setInfo($this->instance->info());
        $stat->setSize($this->instance->dbsize());

        return $stat;
    }
}