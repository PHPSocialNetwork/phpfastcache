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

namespace Phpfastcache\Helper;

use DateInterval;
use DateTime;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOptionInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Exceptions\PhpfastcacheRootException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Traversable;

class Psr16Adapter implements CacheInterface
{
    /**
     * @var ExtendedCacheItemPoolInterface
     */
    protected ExtendedCacheItemPoolInterface $internalCacheInstance;

    /**
     * Psr16Adapter constructor.
     * @param string|ExtendedCacheItemPoolInterface $driver
     * @param null|ConfigurationOptionInterface $config
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheDriverNotFoundException
     */
    public function __construct(string|ExtendedCacheItemPoolInterface $driver, ConfigurationOptionInterface $config = null)
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
     * @param mixed $default
     * @return mixed
     * @throws PhpfastcacheSimpleCacheException
     */
    public function get(string $key, mixed $default = null): mixed
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
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
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
     * @throws InvalidArgumentException
     */
    public function delete(string $key): bool
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
     * @param iterable<string> $keys
     * @param null $default
     * @return ExtendedCacheItemInterface[]
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        if ($keys instanceof Traversable) {
            $keys = \iterator_to_array($keys);
        }
        try {
            return \array_map(
                static fn (ExtendedCacheItemInterface $item) => $item->isHit() ? $item->get() : $default,
                $this->internalCacheInstance->getItems($keys)
            );
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param iterable<string, mixed> $values
     * @param null|int|DateInterval $ttl
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
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
     * @param iterable<string> $keys
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     * @throws InvalidArgumentException
     */
    public function deleteMultiple(iterable $keys): bool
    {
        try {
            if (\is_array($keys)) {
                return $this->internalCacheInstance->deleteItems($keys);
            }

            return $this->internalCacheInstance->deleteItems(\iterator_to_array($keys));
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $key
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     */
    public function has(string $key): bool
    {
        try {
            $cacheItem = $this->internalCacheInstance->getItem($key);
            return $cacheItem->isHit() && !$cacheItem->isExpired();
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }
}
