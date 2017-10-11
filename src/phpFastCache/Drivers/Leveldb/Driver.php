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
use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Core\Pool\IO\IOHelperTrait;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheLogicException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property LeveldbClient $instance Instance of driver service
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait, IOHelperTrait;

    const LEVELDB_FILENAME = '.database';

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
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('Leveldb');
    }

    /**
     * @return bool
     * @throws phpFastCacheLogicException
     */
    protected function driverConnect(): bool
    {
        if ($this->instance instanceof LeveldbClient) {
            throw new phpFastCacheLogicException('Already connected to Leveldb database');
        } else {
            $this->instance = $this->instance ?: new LeveldbClient($this->getLeveldbFile());
        }

        return true;
    }


    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
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
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return (bool) $this->instance->set($item->getKey(), $this->encode($this->driverPreWrap($item)));
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return $this->instance->delete($item->getKey());
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        if ($this->instance instanceof LeveldbClient) {
            $this->instance->close();
            $this->instance = null;
        }
        $result = (bool)LeveldbClient::destroy($this->getLeveldbFile());
        $this->driverConnect();

        return $result;
    }

    /**
     * @return string
     * @throws \phpFastCache\Exceptions\phpFastCacheCoreException
     */
    public function getLeveldbFile(): string
    {
        return $this->getPath() . '/' . self::LEVELDB_FILENAME;
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