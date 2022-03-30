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

namespace Phpfastcache\Cluster\Drivers\SemiReplication;

use Phpfastcache\Cluster\ClusterPoolAbstract;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Exceptions\PhpfastcacheExceptionInterface;
use Phpfastcache\Exceptions\PhpfastcacheReplicationException;
use Psr\Cache\CacheItemInterface;

/**
 * Class FullReplicationCluster
 * @package Phpfastcache\Cluster\Drivers\FullReplication
 */
class SemiReplicationCluster extends ClusterPoolAbstract
{
    /**
     * @inheritDoc
     */
    public function getItem($key)
    {
        /** @var ExtendedCacheItemInterface $item */
        $item = null;
        $eCount = 0;

        foreach ($this->clusterPools as $driverPool) {
            try {
                $poolItem = $driverPool->getItem($key);
                if ($poolItem->isHit()) {
                    if (!$item) {
                        $item = $poolItem;
                        break;
                    }
                }
            } catch (PhpfastcacheExceptionInterface $e) {
                $eCount++;
            }
        }

        if (\count($this->clusterPools) <= $eCount) {
            throw new PhpfastcacheReplicationException('Every pools thrown an exception');
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
        $eCount = 0;
        foreach ($this->clusterPools as $driverPool) {
            try {
                $poolItem = $driverPool->getItem($key);
                if ($poolItem->isHit()) {
                    return true;
                }
            } catch (PhpfastcacheExceptionInterface $e) {
                $eCount++;
            }
        }

        if (\count($this->clusterPools) <= $eCount) {
            throw new PhpfastcacheReplicationException('Every pools thrown an exception');
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $hasClearedOnce = false;
        $eCount = 0;

        foreach ($this->clusterPools as $driverPool) {
            try {
                if ($result = $driverPool->clear()) {
                    $hasClearedOnce = $result;
                }
            } catch (PhpfastcacheExceptionInterface $e) {
                $eCount++;
            }
        }

        if (\count($this->clusterPools) <= $eCount) {
            throw new PhpfastcacheReplicationException('Every pools thrown an exception');
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
        $eCount = 0;

        foreach ($this->clusterPools as $driverPool) {
            try {
                if ($result = $driverPool->deleteItem($key)) {
                    $hasDeletedOnce = $result;
                }
            } catch (PhpfastcacheExceptionInterface $e) {
                $eCount++;
            }
        }

        if (\count($this->clusterPools) <= $eCount) {
            throw new PhpfastcacheReplicationException('Every pools thrown an exception');
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
        $eCount = 0;

        foreach ($this->clusterPools as $driverPool) {
            try {
                $poolItem = $this->getStandardizedItem($item, $driverPool);
                if ($result = $driverPool->save($poolItem)) {
                    $hasSavedOnce = $result;
                }
            } catch (PhpfastcacheExceptionInterface $e) {
                $eCount++;
            }
        }

        if (\count($this->clusterPools) <= $eCount) {
            throw new PhpfastcacheReplicationException('Every pools thrown an exception');
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
        $eCount = 0;

        foreach ($this->clusterPools as $driverPool) {
            try {
                if ($result = $driverPool->commit()) {
                    $hasCommitOnce = $result;
                }
            } catch (PhpfastcacheExceptionInterface $e) {
                $eCount++;
            }
        }

        if (\count($this->clusterPools) <= $eCount) {
            throw new PhpfastcacheReplicationException('Every pools thrown an exception');
        }
        // Return true only if at least one backend confirmed the "commit" operation
        return $hasCommitOnce;
    }
}
