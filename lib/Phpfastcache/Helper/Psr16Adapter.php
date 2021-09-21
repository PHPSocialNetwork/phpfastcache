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

namespace Phpfastcache\Helper;

use DateInterval;
use DateTime;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOptionInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\{PhpfastcacheDriverCheckException,
    PhpfastcacheInvalidArgumentException,
    PhpfastcacheLogicException,
    PhpfastcacheRootException,
    PhpfastcacheSimpleCacheException};
use Psr\SimpleCache\CacheInterface;
use Traversable;

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
     * @param $driver
     * @param null|ConfigurationOptionInterface $config
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
     * @throws \ReflectionException
     */
    public function __construct($driver, ConfigurationOptionInterface $config = null)
    {
        if ($driver instanceof ExtendedCacheItemPoolInterface) {
            if ($config !== null) {
                throw new PhpfastcacheLogicException("You can't pass a config parameter along with an non-string '\$driver' parameter.");
            }
            $this->internalCacheInstance = $driver;
        } else {
            $this->internalCacheInstance = CacheManager::getInstance($driver, $config);
        }
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     * @throws PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get($key, $default = null)
    {
        try {
            $cacheItem = $this->internalCacheInstance->getItem($key);
            if (!$cacheItem->isExpired() && $cacheItem->get() !== null) {
                return $cacheItem->get();
            }

            return $default;
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null|int|DateInterval $ttl
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function set($key, $value, $ttl = null): bool
    {
        try {
            $cacheItem = $this->internalCacheInstance
                ->getItem($key)
                ->set($value);
            if (\is_int($ttl) && $ttl <= 0) {
                $cacheItem->expiresAt((new DateTime('@0')));
            } elseif ($ttl !== null) {
                $cacheItem->expiresAfter($ttl);
            }
            return $this->internalCacheInstance->save($cacheItem);
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $key
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     */
    public function delete($key): bool
    {
        try {
            return $this->internalCacheInstance->deleteItem($key);
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     */
    public function clear(): bool
    {
        try {
            return $this->internalCacheInstance->clear();
        } catch (PhpfastcacheRootException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param iterable $keys
     * @param null $default
     * @return ExtendedCacheItemInterface[]|iterable
     * @throws PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMultiple($keys, $default = null)
    {
        if ($keys instanceof Traversable) {
            $keys = \iterator_to_array($keys);
        }
        try {
            return \array_map(
                static function (ExtendedCacheItemInterface $item) use ($default) {
                    return $item->isHit() ? $item->get() : $default;
                },
                $this->internalCacheInstance->getItems($keys)
            );
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string[] $values
     * @param null|int|DateInterval $ttl
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function setMultiple($values, $ttl = null): bool
    {
        try {
            foreach ($values as $key => $value) {
                $cacheItem = $this->internalCacheInstance->getItem($key)->set($value);

                if (\is_int($ttl) && $ttl <= 0) {
                    $cacheItem->expiresAt((new DateTime('@0')));
                } elseif ($ttl !== null) {
                    $cacheItem->expiresAfter($ttl);
                }
                $this->internalCacheInstance->saveDeferred($cacheItem);
                unset($cacheItem);
            }
            return $this->internalCacheInstance->commit();
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param iterable|array $keys
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function deleteMultiple($keys): bool
    {
        try {
            if ($keys instanceof Traversable) {
                return $this->internalCacheInstance->deleteItems(\iterator_to_array($keys));
            } elseif (\is_array($keys)) {
                return $this->internalCacheInstance->deleteItems($keys);
            } else {
                throw new phpFastCacheInvalidArgumentException('$keys must be an array/Traversable instance.');
            }
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $key
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     */
    public function has($key): bool
    {
        try {
            $cacheItem = $this->internalCacheInstance->getItem($key);
            return $cacheItem->isHit() && !$cacheItem->isExpired();
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Extra methods that are not part of
     * psr16 specifications
     */

    /**
     * @return ExtendedCacheItemPoolInterface
     * @internal
     */
    public function getInternalCacheInstance(): ExtendedCacheItemPoolInterface
    {
        return $this->internalCacheInstance;
    }
}
