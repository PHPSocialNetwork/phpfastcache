<?php

/**
 *
 * This file is part of Phpfastcache.
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
        /** @var ?ExtendedCacheItemInterface $item */
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

                if (\is_object($itemData)) {
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

        $this->resynchronizePool($poolsToResync, $key, $item);

        if ($item === null) {
            $item = new Item($this, $key, $this->getEventManager());
            $item->expiresAfter((int) abs($this->getConfig()->getDefaultTtl()));
        }

        return $this->getStandardizedItem($item, $this);
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

    /**
     * @param ExtendedCacheItemPoolInterface[] $poolsToResynchronize
     * @param string $key
     * @param ?ExtendedCacheItemInterface $item
     * @return void
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
     */
    protected function resynchronizePool(array $poolsToResynchronize, string $key, ?ExtendedCacheItemInterface $item): void
    {
        if ($item && $item->isHit() && \count($poolsToResynchronize) < \count($this->clusterPools)) {
            foreach ($poolsToResynchronize as $poolToResynchronize) {
                $poolItem = $poolToResynchronize->getItem($key);
                $poolItem->setEventManager($this->getEventManager())
                    ->set($item->get())
                    ->setHit($item->isHit())
                    ->setTags($item->getTags())
                    ->expiresAt($item->getExpirationDate())
                    ->setDriver($poolToResynchronize);
                $poolToResynchronize->save($poolItem);
            }
        }
    }
}
