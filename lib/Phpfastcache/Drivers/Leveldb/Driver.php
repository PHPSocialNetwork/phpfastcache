<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Leveldb;

use LevelDB as LeveldbClient;
use Phpfastcache\Core\Pool\{
    DriverBaseTrait, ExtendedCacheItemPoolInterface, IO\IOHelperTrait
};
use Phpfastcache\Exceptions\{
    PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException
};
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property LeveldbClient $instance Instance of driver service
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait, IOHelperTrait;

    const LEVELDB_FILENAME = '.database';

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \extension_loaded('Leveldb');
    }

    /**
     * @return bool
     * @throws PhpfastcacheLogicException
     */
    protected function driverConnect(): bool
    {
        if ($this->instance instanceof LeveldbClient) {
            throw new PhpfastcacheLogicException('Already connected to Leveldb database');
        }

        $this->instance = $this->instance ?: new LeveldbClient($this->getLeveldbFile());

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
        }

        return $this->decode($val);
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return (bool)$this->instance->set($item->getKey(), $this->encode($this->driverPreWrap($item)));
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return $this->instance->delete($item->getKey());
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
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
     * @throws \Phpfastcache\Exceptions\PhpfastcacheCoreException
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