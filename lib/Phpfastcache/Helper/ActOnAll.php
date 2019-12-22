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

use Phpfastcache\{
    CacheManager, Event\EventInterface
};
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Entities\DriverStatistic;
use Psr\Cache\CacheItemInterface;

/**
 * Class ActOnAll
 * @package phpFastCache\Helper
 * @todo Review the setters part due to a confusion with cross-driver items
 */
class ActOnAll
{
    /**
     * @var ExtendedCacheItemPoolInterface[]
     */
    protected $instances = [];

    /**
     * @deprecated as of 7.1.1 will be removed in 8.x (Replaced by Cluster Aggregation feature)
     * ActOnAll constructor.
     */
    public function __construct()
    {
        @\trigger_error(\sprintf('Class "%s" is deprecated and will be removed in the next major release (8.x).', static::class), \E_USER_DEPRECATED);
        $this->instances =& CacheManager::getInternalInstances();
    }

    /**
     * @return \Closure
     */
    protected function getGenericCallback(): \Closure
    {
        return function ($method, $args) {
            $return = [];
            foreach ($this->instances as $instance) {
                $reflectionMethod = new \ReflectionMethod(\get_class($instance), $method);
                $return[$instance->getDriverName()] = $reflectionMethod->invokeArgs($instance, $args);
            }
            return $return;
        };
    }


    /**
     * @param string $key
     * @return array
     */
    public function hasItem($key): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @return array
     */
    public function clear(): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param string $key
     * @return array
     */
    public function deleteItem($key): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $keys
     * @return array
     */
    public function deleteItems(array $keys): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return array
     */
    public function save(CacheItemInterface $item): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return array
     */
    public function saveDeferred(CacheItemInterface $item): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array ...$items
     * @return array
     */
    public function saveMultiple(...$items): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @return array
     */
    public function commit(): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param string $optionName
     * @return array
     */
    public function getConfigOption(string $optionName): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @return array
     */
    public function getDriverName(): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param string $key
     * @return array
     */
    public function getItem($key): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $keys
     * @return array
     */
    public function getItems(array $keys = []): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $keys
     * @param int $option
     * @param int $depth
     * @return array
     */
    public function getItemsAsJsonString(array $keys = [], $option = 0, $depth = 512): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return array
     */
    public function setItem(CacheItemInterface $item): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @return string[]
     */
    public function getHelp(): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @return DriverStatistic[]
     */
    public function getStats(): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param string $tagName
     * @return array
     */
    public function getItemsByTag($tagName): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @return array
     */
    public function getItemsByTags(array $tagNames): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @param int $option
     * @param int $depth
     * @return array
     */
    public function getItemsByTagsAsJsonString(array $tagNames, $option = 0, $depth = 512): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param string $tagName
     * @return array
     */
    public function deleteItemsByTag($tagName): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @return array
     */
    public function deleteItemsByTags(array $tagNames): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param string $tagName
     * @param int $step
     * @return array
     */
    public function incrementItemsByTag($tagName, $step = 1): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @param int $step
     * @return array
     */
    public function incrementItemsByTags(array $tagNames, $step = 1): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param string $tagName
     * @param int $step
     * @return array
     */
    public function decrementItemsByTag($tagName, $step = 1): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @param int $step
     * @return array
     */
    public function decrementItemsByTags(array $tagNames, $step = 1): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param string $tagName
     * @param array|string $data
     * @return array
     */
    public function appendItemsByTag($tagName, $data): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @param array|string $data
     * @return array
     */
    public function appendItemsByTags(array $tagNames, $data): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param string $tagName
     * @param array|string $data
     * @return array
     */
    public function prependItemsByTag($tagName, $data): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @param array|string $data
     * @return array
     */
    public function prependItemsByTags(array $tagNames, $data): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @return array
     */
    public function getItemsByTagsAll(array $tagNames): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @return array
     */
    public function deleteItemsByTagsAll(array $tagNames): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @param int $step
     * @return array
     */
    public function incrementItemsByTagsAll(array $tagNames, $step = 1): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @param int $step
     * @return array
     */
    public function decrementItemsByTagsAll(array $tagNames, $step = 1): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @param array|string $data
     * @return array
     */
    public function appendItemsByTagsAll(array $tagNames, $data): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param array $tagNames
     * @param array|string $data
     * @return array
     */
    public function prependItemsByTagsAll(array $tagNames, $data): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return array
     */
    public function detachItem(CacheItemInterface $item): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @return array
     */
    public function detachAllItems(): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return array
     */
    public function attachItem(CacheItemInterface $item): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return array
     */
    public function isAttached(CacheItemInterface $item): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @param EventInterface $em
     * @return array
     */
    public function setEventManager(EventInterface $em): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }

    /**
     * @return array
     */
    public function getDefaultConfig(): array
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, \func_get_args());
    }
}