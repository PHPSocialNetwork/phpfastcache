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
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\EventManager;
use Psr\Cache\CacheItemInterface;

/**
 * Class ActOnAll
 * @package phpFastCache\Helper
 */
class ActOnAll implements ExtendedCacheItemPoolInterface
{

    /**
     * @var ExtendedCacheItemPoolInterface[]
     */
    protected $instances = [];

    /**
     * ActOnAll constructor.
     */
    public function __construct()
    {
        $this->instances =& CacheManager::getInternalInstances();
    }

    /**
     * @return \Closure
     */
    protected function getGenericCallback()
    {
        return function ($method, $args) {
            $getterMethod = (strpos($method, 'get') === 0);
            $return = false;

            if ($getterMethod) {
                $return = [];
            }

            foreach ($this->instances as $instance) {
                $reflectionMethod = new \ReflectionMethod(get_class($instance), $method);
                if ($getterMethod) {
                    $return[ $instance->getDriverName() ] = $reflectionMethod->invokeArgs($instance, $args);
                } else {
                    $result = $reflectionMethod->invokeArgs($instance, $args);
                    if ($result !== false) {
                        $return = $result;
                    }
                }
            }
            return $return;
        };
    }


    /**
     * @param string $key
     * @return mixed
     */
    public function hasItem($key)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     */
    public function clear()
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function deleteItem($key)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $keys
     * @return mixed
     */
    public function deleteItems(array $keys)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    public function save(CacheItemInterface $item)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array ...$items
     * @return mixed
     */
    public function saveMultiple(...$items)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     */
    public function commit()
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     */
    public function getConfigOption($optionName)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     */
    public function getDriverName()
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getItem($key)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $keys
     * @return mixed
     */
    public function getItems(array $keys = [])
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $keys
     * @param int $option
     * @param int $depth
     * @return mixed
     */
    public function getItemsAsJsonString(array $keys = [], $option = 0, $depth = 512)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    public function setItem(CacheItemInterface $item)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     */
    public function getHelp()
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     */
    public function getStats()
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $tagName
     * @return mixed
     */
    public function getItemsByTag($tagName)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @return mixed
     */
    public function getItemsByTags(array $tagNames)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @param int $option
     * @param int $depth
     * @return mixed
     */
    public function getItemsByTagsAsJsonString(array $tagNames, $option = 0, $depth = 512)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $tagName
     * @return mixed
     */
    public function deleteItemsByTag($tagName)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @return mixed
     */
    public function deleteItemsByTags(array $tagNames)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $tagName
     * @param int $step
     * @return mixed
     */
    public function incrementItemsByTag($tagName, $step = 1)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @param int $step
     * @return mixed
     */
    public function incrementItemsByTags(array $tagNames, $step = 1)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $tagName
     * @param int $step
     * @return mixed
     */
    public function decrementItemsByTag($tagName, $step = 1)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @param int $step
     * @return mixed
     */
    public function decrementItemsByTags(array $tagNames, $step = 1)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $tagName
     * @param array|string $data
     * @return mixed
     */
    public function appendItemsByTag($tagName, $data)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @param array|string $data
     * @return mixed
     */
    public function appendItemsByTags(array $tagNames, $data)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $tagName
     * @param array|string $data
     * @return mixed
     */
    public function prependItemsByTag($tagName, $data)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @param array|string $data
     * @return mixed
     */
    public function prependItemsByTags(array $tagNames, $data)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @return mixed
     */
    public function getItemsByTagsAll(array $tagNames)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @return mixed
     */
    public function deleteItemsByTagsAll(array $tagNames)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @param int $step
     * @return mixed
     */
    public function incrementItemsByTagsAll(array $tagNames, $step = 1)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @param int $step
     * @return mixed
     */
    public function decrementItemsByTagsAll(array $tagNames, $step = 1)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @param array|string $data
     * @return mixed
     */
    public function appendItemsByTagsAll(array $tagNames, $data)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $tagNames
     * @param array|string $data
     * @return mixed
     */
    public function prependItemsByTagsAll(array $tagNames, $data)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    public function detachItem(CacheItemInterface $item)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     */
    public function detachAllItems()
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    public function attachItem(CacheItemInterface $item)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    public function isAttached(CacheItemInterface $item)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }

    /**
     * @param \phpFastCache\EventManager $em
     * @return mixed
     */
    public function setEventManager(EventManager $em)
    {
        $callback = $this->getGenericCallback();
        return $callback(__FUNCTION__, func_get_args());
    }
}