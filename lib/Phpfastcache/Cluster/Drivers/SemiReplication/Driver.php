<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Georges.L (Geolim4) <contact@geolim4.com>
 *
 */

declare(strict_types=1);

namespace Phpfastcache\Cluster\Drivers\SemiReplication;

use Phpfastcache\Cluster\ClusterPoolAbstract;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Exceptions\PhpfastcacheExceptionInterface;
use Phpfastcache\Exceptions\PhpfastcacheReplicationException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;

class Driver extends ClusterPoolAbstract
{
    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     * @throws PhpfastcacheReplicationException
     */
    public function getItem(string $key): ExtendedCacheItemInterface
    {
        /** @var ?ExtendedCacheItemInterface $item */
        $item = null;
        $eCount = 0;

        foreach ($this->clusterPools as $driverPool) {
            try {
                $poolItem = $driverPool->getItem($key);
                if (!$item && $poolItem->isHit()) {
                    $item = $poolItem;
                    break;
                }
            } catch (PhpfastcacheExceptionInterface) {
                $eCount++;
            }
        }

        if (\count($this->clusterPools) <= $eCount) {
            throw new PhpfastcacheReplicationException('Every pools thrown an exception');
        }

        if ($item === null) {
            $item = new Item($this, $key, $this->getEventManager());
            $item->expiresAfter((int) abs($this->getConfig()->getDefaultTtl()));
        }

        return $this->getStandardizedItem($item, $this);
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheReplicationException
     */
    public function hasItem(string $key): bool
    {
        $eCount = 0;
        foreach ($this->clusterPools as $driverPool) {
            try {
                $poolItem = $driverPool->getItem($key);
                if ($poolItem->isHit()) {
                    return true;
                }
            } catch (PhpfastcacheExceptionInterface) {
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
     * @throws PhpfastcacheReplicationException
     */
    public function clear(): bool
    {
        $hasClearedOnce = false;
        $eCount = 0;

        foreach ($this->clusterPools as $driverPool) {
            try {
                if ($result = $driverPool->clear()) {
                    $hasClearedOnce = $result;
                }
            } catch (PhpfastcacheExceptionInterface) {
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
     * @throws PhpfastcacheReplicationException
     * @throws InvalidArgumentException
     */
    public function deleteItem(string $key): bool
    {
        $hasDeletedOnce = false;
        $eCount = 0;

        foreach ($this->clusterPools as $driverPool) {
            try {
                if ($result = $driverPool->deleteItem($key)) {
                    $hasDeletedOnce = $result;
                }
            } catch (PhpfastcacheExceptionInterface) {
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
     * @param CacheItemInterface $item
     * @return bool
     * @throws InvalidArgumentException
     * @throws PhpfastcacheReplicationException
     */
    public function save(CacheItemInterface $item): bool
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
            } catch (PhpfastcacheExceptionInterface) {
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
     * @throws PhpfastcacheReplicationException
     */
    public function commit(): bool
    {
        $hasCommitOnce = false;
        $eCount = 0;

        foreach ($this->clusterPools as $driverPool) {
            try {
                if ($result = $driverPool->commit()) {
                    $hasCommitOnce = $result;
                }
            } catch (PhpfastcacheExceptionInterface) {
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
