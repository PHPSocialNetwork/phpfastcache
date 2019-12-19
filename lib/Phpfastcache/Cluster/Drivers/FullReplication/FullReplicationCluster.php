<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author  Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Cluster\Drivers\FullReplication;

use Phpfastcache\Cluster\ClusterPoolAbstract;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Psr\Cache\CacheItemInterface;

/**
 * Class FullReplicationCluster
 * @package Phpfastcache\Cluster\Drivers\FullReplication
 */
class FullReplicationCluster extends ClusterPoolAbstract
{

    /**
     * @inheritDoc
     */
    public function getItem($key)
    {
        /** @var \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface[] $poolsToResync */
        $poolsToResync = [];
        /** @var \Phpfastcache\Core\Item\ExtendedCacheItemInterface $item */
        $item = NULL;

        foreach ($this->driverPools as $driverPool) {
            $poolItem = $driverPool->getItem($key);
            if ($poolItem->isHit()) {
                if(!$item){
                    $item = $poolItem;
                    continue;
                }

                $itemData = $item->get();
                $poolItemData = $poolItem->get();

                if (\is_object($itemData)
                ) {
                    if ($item->get() != $poolItemData) {
                        $poolsToResync[] = $driverPool;
                    }
                } else {
                    if ($item->get() !== $poolItemData) {
                        $poolsToResync[] = $driverPool;
                    }
                }
            } else {
                $poolsToResync[] = $driverPool;
            }
        }

        if ($item && $item->isHit() && \count($poolsToResync) < \count($this->driverPools)) {
            foreach ($poolsToResync as $poolToResync) {
                $poolItem = $poolToResync->getItem($key);
                $poolItem->set($item->get())
                    ->expiresAt($item->getExpirationDate());
                $poolToResync->save($poolItem);
            }
        }

        return $this->getStandardizedItem($item ?? new Item($this, $key), $this);
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = [])
    {
        $items = [];

        foreach ($keys as $key) {
            $items[] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
        foreach ($this->driverPools as $driverPool) {
            $poolItem = $driverPool->getItem($key);
            if ($poolItem->isHit()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $hasClearedOnce = false;
        foreach ($this->driverPools as $driverPool) {
            if ($result = $driverPool->clear()) {
                $hasClearedOnce = $result;
            }
        }
        // Return true only if at least one backend confirmed the "clear" operation
        return $hasClearedOnce;
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key)
    {
        $hasDeletedOnce = false;
        foreach ($this->driverPools as $driverPool) {
            if ($result = $driverPool->deleteItem($key)) {
                $hasDeletedOnce = $result;
            }
        }
        // Return true only if at least one backend confirmed the "clear" operation
        return $hasDeletedOnce;
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys)
    {
        $hasDeletedOnce = false;
        foreach ($this->driverPools as $driverPool) {
            if ($result = $driverPool->deleteItems($keys)) {
                $hasDeletedOnce = $result;
            }
        }
        // Return true only if at least one backend confirmed the "clear" operation
        return $hasDeletedOnce;
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item)
    {
        /** @var ExtendedCacheItemInterface $item */
        $hasSavedOnce = false;
        foreach ($this->driverPools as $driverPool) {
            $poolItem = $this->getStandardizedItem($item, $driverPool);
            \var_dump(\get_class($driverPool));
            if ($result = $driverPool->save($poolItem)) {
                $hasSavedOnce = $result;
            }
        }
        // Return true only if at least one backend confirmed the "commit" operation
        return $hasSavedOnce;
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        /** @var ExtendedCacheItemInterface $item */
        $hasSavedOnce = false;
        foreach ($this->driverPools as $driverPool) {
            $poolItem = $this->getStandardizedItem($item, $driverPool);
            if ($result = $driverPool->saveDeferred($poolItem)) {
                $hasSavedOnce = $result;
            }
        }
        // Return true only if at least one backend confirmed the "commit" operation
        return $hasSavedOnce;
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        $hasCommitOnce = false;
        foreach ($this->driverPools as $driverPool) {
            if ($result = $driverPool->commit()) {
                $hasCommitOnce = $result;
            }
        }
        // Return true only if at least one backend confirmed the "commit" operation
        return $hasCommitOnce;
    }


}
