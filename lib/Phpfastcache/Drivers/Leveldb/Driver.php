<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

declare(strict_types=1);

namespace Phpfastcache\Drivers\Leveldb;

use LevelDB as LeveldbClient;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\IO\IOHelperTrait;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * @property LeveldbClient|null $instance Instance of driver service
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use IOHelperTrait;

    protected const LEVELDB_FILENAME = '.database';

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('Leveldb');
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

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $val = $this->instance->get($item->getKey());
        if (!$val) {
            return null;
        }

        return $this->decode($val);
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return (bool)$this->instance->set($item->getKey(), $this->encode($this->driverPreWrap($item)));
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return $this->instance->delete($item->getKey());
    }

    /**
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     */
    protected function driverClear(): bool
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
     * @return string
     * @throws PhpfastcacheCoreException
     */
    public function getLeveldbFile(): string
    {
        return $this->getPath() . '/' . self::LEVELDB_FILENAME;
    }

    /**
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     */
    protected function driverConnect(): bool
    {
        if ($this->instance instanceof LeveldbClient) {
            throw new PhpfastcacheLogicException('Already connected to Leveldb database');
        }

        $this->instance = new LeveldbClient($this->getLeveldbFile());

        return true;
    }
}
