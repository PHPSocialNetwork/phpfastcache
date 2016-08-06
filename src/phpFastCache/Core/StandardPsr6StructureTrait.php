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
use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheCoreException;
use Psr\Cache\CacheItemInterface;

/**
 * Trait StandardPsr6StructureTrait
 * @package phpFastCache\Core
 */
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
     * @throws phpFastCacheCoreException
     */
    public function getItem($key)
    {
        if (is_string($key)) {
            if (!array_key_exists($key, $this->itemInstances)) {

                /**
                 * @var $item ExtendedCacheItemInterface
                 */
                CacheManager::$ReadHits++;
                $class = new \ReflectionClass((new \ReflectionObject($this))->getNamespaceName() . '\Item');
                $item = $class->newInstanceArgs([$this, $key]);
                $driverArray = $this->driverRead($item);

                if ($driverArray) {
                    if(!is_array($driverArray)){
                        throw new phpFastCacheCoreException(sprintf('The driverRead method returned an unexpected variable type: %s', gettype($driverArray)));
                    }
                    $item->set($this->driverUnwrapData($driverArray));
                    $item->expiresAt($this->driverUnwrapTime($driverArray));
                    $item->setTags($this->driverUnwrapTags($driverArray));
                    if ($item->isExpired()) {
                        /**
                         * Using driverDelete() instead of delete()
                         * to avoid infinite loop caused by
                         * getItem() call in delete() method
                         * As we MUST return an item in any
                         * way, we do not de-register here
                         */
                        $this->driverDelete($item);

                    } else {
                        $item->setHit(true);
                    }
                } else {
                    $item->expiresAfter(abs((int) $this->getConfig()[ 'defaultTtl' ]));
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
        CacheManager::$ReadHits++;

        return $this->getItem($key)->isHit();
    }

    /**
     * @return bool
     */
    public function clear()
    {
        CacheManager::$WriteHits++;
        $this->itemInstances = [];

        return $this->driverClear();
    }

    /**
     * @param string $key
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function deleteItem($key)
    {
        $item = $this->getItem($key);
        if ($this->hasItem($key) && $this->driverDelete($item)) {
            $item->setHit(false);
            CacheManager::$WriteHits++;
            /**
             * De-register the item instance
             * then collect gc cycles
             */
            $this->deregisterItem($key);

            return true;
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

        return (bool) $return;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function save(CacheItemInterface $item)
    {
        /**
         * @var ExtendedCacheItemInterface $item
         */
        if (!array_key_exists($item->getKey(), $this->itemInstances)) {
            $this->itemInstances[ $item->getKey() ] = $item;
        } else if(spl_object_hash($item) !== spl_object_hash($this->itemInstances[ $item->getKey() ])){
            throw new \RuntimeException('Spl object hash mismatches ! You probably tried to save a detached item which has been already retrieved from cache.');
        }


        if ($this->driverWrite($item) && $this->driverWriteTags($item)) {
            $item->setHit(true);
            CacheManager::$WriteHits++;

            return true;
        }

        return false;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return \Psr\Cache\CacheItemInterface
     * @throws \RuntimeException
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        if (!array_key_exists($item->getKey(), $this->itemInstances)) {
            $this->itemInstances[ $item->getKey() ] = $item;
        }else if(spl_object_hash($item) !== spl_object_hash($this->itemInstances[ $item->getKey() ])){
            throw new \RuntimeException('Spl object hash mismatches ! You probably tried to save a detached item which has been already retrieved from cache.');
        }

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
            $result = $this->save($item);
            if ($return !== false) {
                unset($this->deferredList[ $key ]);
                $return = $result;
            }
        }

        return (bool) $return;
    }
}