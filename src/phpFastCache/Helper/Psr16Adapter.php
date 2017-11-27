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

namespace phpFastCache\Helper;

use phpFastCache\CacheManager;
use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheRootException;
use phpFastCache\Exceptions\phpFastCacheSimpleCacheException;
use Psr\SimpleCache\CacheInterface;

/**
 * Class Psr16Adapter
 * @package phpFastCache\Helper
 */
class Psr16Adapter implements CacheInterface
{
    /**
     * @var ExtendedCacheItemPoolInterface
     */
    protected $internalCacheInstance;

    /**
     * Psr16Adapter constructor.
     * @param string $driver
     * @param array $config
     * @throws phpFastCacheDriverCheckException
     */
    public function __construct($driver, array $config = [])
    {
        $this->internalCacheInstance = CacheManager::getInstance($driver, $config);
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function get($key, $default = null)
    {
        try {
            $cacheItem = $this->internalCacheInstance->getItem($key);
            if (!$cacheItem->isExpired() && $cacheItem->get() !== null) {
                return $cacheItem->get();
            } else {
                return $default;
            }
        } catch (phpFastCacheInvalidArgumentException $e) {
            throw new phpFastCacheSimpleCacheException($e->getMessage(), null, $e);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     * @return bool
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function set($key, $value, $ttl = null)
    {
        try {
            $cacheItem = $this->internalCacheInstance
              ->getItem($key)
              ->set($value);
            if (is_int($ttl) && $ttl <= 0) {
                $cacheItem->expiresAt((new \DateTime('@0')));
            } elseif (is_int($ttl) || $ttl instanceof \DateInterval) {
                $cacheItem->expiresAfter($ttl);
            }
            return $this->internalCacheInstance->save($cacheItem);
        } catch (phpFastCacheInvalidArgumentException $e) {
            throw new phpFastCacheSimpleCacheException($e->getMessage(), null, $e);
        }
    }

    /**
     * @param string $key
     * @return bool
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function delete($key)
    {
        try {
            return $this->internalCacheInstance->deleteItem($key);
        } catch (phpFastCacheInvalidArgumentException $e) {
            throw new phpFastCacheSimpleCacheException($e->getMessage(), null, $e);
        }
    }

    /**
     * @return bool
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function clear()
    {
        try {
            return $this->internalCacheInstance->clear();
        } catch (phpFastCacheRootException $e) {
            throw new phpFastCacheSimpleCacheException($e->getMessage(), null, $e);
        }
    }

    /**
     * @param string[] $keys
     * @param null $default
     * @return \iterable
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function getMultiple($keys, $default = null)
    {
        try {
            return array_map(function (ExtendedCacheItemInterface $item) {
                return $item->get();
            }, $this->internalCacheInstance->getItems($keys));
        } catch (phpFastCacheInvalidArgumentException $e) {
            throw new phpFastCacheSimpleCacheException($e->getMessage(), null, $e);
        }
    }

    /**
     * @param string[] $values
     * @param null|int|\DateInterval $ttl
     * @return bool
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function setMultiple($values, $ttl = null)
    {
        try {
            foreach ($values as $key => $value) {
                $cacheItem = $this->internalCacheInstance->getItem($key)->set($value);

                if (is_int($ttl) && $ttl <= 0) {
                    $cacheItem->expiresAt((new \DateTime('@0')));
                } elseif (is_int($ttl) || $ttl instanceof \DateInterval) {
                    $cacheItem->expiresAfter($ttl);
                }
                $this->internalCacheInstance->saveDeferred($cacheItem);
                unset($cacheItem);
            }
            return $this->internalCacheInstance->commit();
        } catch (phpFastCacheInvalidArgumentException $e) {
            throw new phpFastCacheSimpleCacheException($e->getMessage(), null, $e);
        }
    }

    /**
     * @param string[] $keys
     * @return bool
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function deleteMultiple($keys)
    {
        try {
            return $this->internalCacheInstance->deleteItems($keys);
        } catch (phpFastCacheInvalidArgumentException $e) {
            throw new phpFastCacheSimpleCacheException($e->getMessage(), null, $e);
        }
    }

    /**
     * @param string $key
     * @return bool
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function has($key)
    {
        try {
            $cacheItem = $this->internalCacheInstance->getItem($key);
            return $cacheItem->isHit() && !$cacheItem->isExpired();
        } catch (phpFastCacheInvalidArgumentException $e) {
            throw new phpFastCacheSimpleCacheException($e->getMessage(), null, $e);
        }
    }
}
