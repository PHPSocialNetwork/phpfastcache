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

namespace phpFastCache\Drivers\Leveldb;

use LevelDB as LeveldbClient;
use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\PathSeekerTrait;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Util\Directory;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    use PathSeekerTrait;

    const LEVELDB_FILENAME = '.database';

    /**
     * @var LeveldbClient Instance of driver service
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
        } else {
            $this->driverConnect();
        }
    }

    /**
     * @return string
     * @throws \phpFastCache\Exceptions\phpFastCacheCoreException
     */
    public function getLeveldbFile()
    {
        return $this->getPath() . '/' . self::LEVELDB_FILENAME;
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        return extension_loaded('Leveldb');
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
            return $this->instance->set($item->getKey(), $this->encode($this->driverPreWrap($item)));
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
        $val = $this->instance->get($item->getKey());
        if ($val == false) {
            return null;
        } else {
            return $this->decode($val);
        }
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
            return $this->instance->delete($item->getKey());
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        if ($this->instance instanceof LeveldbClient) {
            $this->instance->close();
            $this->instance = null;
        }
        $result = LeveldbClient::destroy($this->getLeveldbFile());
        $this->driverConnect();

        return $result;
    }

    /**
     * @return bool
     */
    protected function driverConnect()
    {
        if ($this->instance instanceof LeveldbClient) {
            throw new \LogicException('Already connected to Leveldb database');
        } else {
            $this->instance = $this->instance ?: new LeveldbClient($this->getLeveldbFile());
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
        return (new driverStatistic())
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setInfo('Number of files used to build the cache: ' . Directory::getFileCount($this->getLeveldbFile()))
          ->setSize(Directory::dirSize($this->getLeveldbFile()));
    }

    /**
     * Close connection on destruct
     */
    public function __destruct()
    {
        if ($this->instance instanceof LeveldbClient) {
            $this->instance->close();
            $this->instance = null;
        }
    }
}