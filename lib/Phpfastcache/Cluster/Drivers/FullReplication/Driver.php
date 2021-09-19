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
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

class Driver extends ClusterPoolAbstract
{
    /**
     * @inheritDoc
     */
    public function getItem(string $key): ExtendedCacheItemInterface
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

        return $this->getStandardizedItem($item ?? new Item($this, $key, $this->getEventManager()), $this);
    }

    /**
     * @inheritDoc
     */
    public function hasItem(string $key): bool
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
    public function clear(): bool
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
    public function deleteItem(string $key): bool
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
    public function save(CacheItemInterface $item): bool
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
    public function saveDeferred(CacheItemInterface $item): bool
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
    public function commit(): bool
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
