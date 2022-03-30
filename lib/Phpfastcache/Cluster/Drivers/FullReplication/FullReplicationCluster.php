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
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
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
        /** @var ExtendedCacheItemPoolInterface[] $poolsToResync */
        $poolsToResync = [];
        /** @var ExtendedCacheItemInterface $item */
        $item = null;

        foreach ($this->clusterPools as $driverPool) {
            $poolItem = $driverPool->getItem($key);
            if ($poolItem->isHit()) {
                if (!$item) {
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

        if ($item && $item->isHit() && \count($poolsToResync) < \count($this->clusterPools)) {
            foreach ($poolsToResync as $poolToResync) {
                $poolItem = $poolToResync->getItem($key);
                $poolItem->setEventManager($this->getEventManager())
                    ->set($item->get())
                    ->setHit($item->isHit())
                    ->setTags($item->getTags())
                    ->expiresAt($item->getExpirationDate())
                    ->setDriver($poolToResync);
                $poolToResync->save($poolItem);
            }
        }

        if ($item === null) {
            $item = new Item($this, $key, $this->getEventManager());
            $item->expiresAfter(abs($this->getConfig()->getDefaultTtl()));
        }

        return $this->getStandardizedItem($item, $this);
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
        foreach ($this->clusterPools as $driverPool) {
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
        foreach ($this->clusterPools as $driverPool) {
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
        foreach ($this->clusterPools as $driverPool) {
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
    public function save(CacheItemInterface $item)
    {
        /** @var ExtendedCacheItemInterface $item */
        $hasSavedOnce = false;
        foreach ($this->clusterPools as $driverPool) {
            $poolItem = $this->getStandardizedItem($item, $driverPool);
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
        foreach ($this->clusterPools as $driverPool) {
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
        foreach ($this->clusterPools as $driverPool) {
            if ($result = $driverPool->commit()) {
                $hasCommitOnce = $result;
            }
        }
        // Return true only if at least one backend confirmed the "commit" operation
        return $hasCommitOnce;
    }


}
