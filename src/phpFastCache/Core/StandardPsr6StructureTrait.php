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

namespace phpFastCache\Core;

use phpFastCache\Cache\ExtendedCacheItemInterface;
use Psr\Cache\CacheItemInterface;

trait StandardPsr6StructureTrait
{
    use ClassNamespaceResolverTrait;

    /**
     * @var array
     */
    protected $deferredList = [];

    /**
     * @var ExtendedCacheItemInterface[]
     */
    protected $itemInstances = [];

    /**
     * @param string $key
     * @return \phpFastCache\Cache\ExtendedCacheItemInterface
     * @throws \InvalidArgumentException
     */
    public function getItem($key)
    {
        if (is_string($key)) {
            if (!array_key_exists($key, $this->itemInstances)) {
                //(new \ReflectionObject($this))->getNamespaceName()

                /**
                 * @var $item ExtendedCacheItemInterface
                 */
                $class = new \ReflectionClass((new \ReflectionObject($this))->getNamespaceName() . '\Item');
                $item = $class->newInstanceArgs(array($this, $key));
                $driverArray = $this->driverRead($key);
                if($driverArray)
                {
                    $item->set($this->driverUnwrapData($driverArray));
                    $item->expiresAt($this->driverUnwrapTime($driverArray));
                }

            }
        } else {
            throw new \InvalidArgumentException(sprintf('$key must be a string, got type "%s" instead.', gettype($key)));
        }

        return $this->itemInstances[ $key ];
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setItem(CacheItemInterface $item)
    {
        if ($this->getClassNamespace() . '\\Item' === get_class($item)) {
            $this->itemInstances[ $item->getKey() ] = $item;

            return $this;
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid Item Class "%s" for this driver.', get_class($item)));
        }
    }

    /**
     * @param array $keys
     * @return CacheItemInterface[]
     * @throws \InvalidArgumentException
     */
    public function getItems(array $keys = [])
    {
        $collection = [];
        foreach ($keys as $key) {
            $collection[ $key ] = $this->getItem($key);
        }

        return $collection;
    }

    /**
     * @param string $key
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return $this->driverClear();
    }

    /**
     * @param string $key
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function deleteItem($key)
    {
        if ($this->hasItem($key)) {
            return $this->driverDelete($this->getItem($key));
        }

        return false;
    }

    /**
     * @param array $keys
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function deleteItems(array $keys)
    {
        $return = null;
        foreach ($keys as $key) {
            $result = $this->deleteItem($key);
            if ($result !== false) {
                $return = $result;
            }
        }

        return (bool)$return;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function save(CacheItemInterface $item)
    {
        return $this->driverWrite($item);
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return \Psr\Cache\CacheItemInterface
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->deferredList[ $item->getKey() ] = $item;
    }

    /**
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    public function commit()
    {
        $return = null;
        foreach ($this->deferredList as $key => $item) {
            $result = $this->driverWrite($item);
            if ($return !== false) {
                unset($this->deferredList[ $key ]);
                $return = $result;
            }
        }

        return (bool)$return;
    }
}