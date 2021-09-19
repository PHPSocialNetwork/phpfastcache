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

namespace Phpfastcache\Cluster\Drivers\MasterSlaveReplication;

use Phpfastcache\Cluster\ClusterPoolAbstract;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\EventManager;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheExceptionInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheReplicationException;
use Psr\Cache\CacheItemInterface;
use ReflectionException;

class Driver extends ClusterPoolAbstract
{
    /**
     * MasterSlaveReplicationCluster constructor.
     * @param string $clusterName
     * @param EventManager $em
     * @param ExtendedCacheItemPoolInterface ...$driverPools
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverConnectException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheIOException
     */
    public function __construct(string $clusterName, EventManager $em, ExtendedCacheItemPoolInterface ...$driverPools)
    {
        if (\count($driverPools) !== 2) {
            throw new PhpfastcacheInvalidArgumentException('A "master/slave" cluster requires exactly two pools to be working.');
        }

        parent::__construct($clusterName, $em, ...$driverPools);
    }

    /**
     * @inheritDoc
     */
    public function getItem(string $key): ExtendedCacheItemInterface
    {
        return $this->getStandardizedItem(
            $this->makeOperation(static fn (ExtendedCacheItemPoolInterface $pool) => $pool->getItem($key)) ?? new Item($this, $key, $this->getEventManager()),
            $this
        );
    }

    /**
     * @param callable $operation
     * @return mixed
     * @throws PhpfastcacheReplicationException
     */
    protected function makeOperation(callable $operation)
    {
        try {
            return $operation($this->getMasterPool());
        } catch (PhpfastcacheExceptionInterface $e) {
            try {
                $this->eventManager->dispatch(
                    'CacheReplicationSlaveFallback',
                    $this,
                    \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function']
                );
                return $operation($this->getSlavePool());
            } catch (PhpfastcacheExceptionInterface $e) {
                throw new PhpfastcacheReplicationException('Master and Slave thrown an exception !');
            }
        }
    }

    /**
     * @return ExtendedCacheItemPoolInterface
     */
    protected function getMasterPool(): ExtendedCacheItemPoolInterface
    {
        return $this->clusterPools[0];
    }

    /**
     * @return ExtendedCacheItemPoolInterface
     */
    protected function getSlavePool(): ExtendedCacheItemPoolInterface
    {
        return $this->clusterPools[1];
    }

    /**
     * @inheritDoc
     */
    public function hasItem(string $key): bool
    {
        return $this->makeOperation(
            static fn (ExtendedCacheItemPoolInterface $pool) => $pool->hasItem($key)
        );
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->makeOperation(
            static fn (ExtendedCacheItemPoolInterface $pool) => $pool->clear()
        );
    }

    /**
     * @inheritDoc
     */
    public function deleteItem(string $key): bool
    {
        return $this->makeOperation(
            static fn (ExtendedCacheItemPoolInterface $pool) => $pool->deleteItem($key)
        );
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item): bool
    {
        return $this->makeOperation(
            function (ExtendedCacheItemPoolInterface $pool) use ($item) {
                /** @var ExtendedCacheItemInterface $item */
                $item->setHit(true);
                return $pool->save($this->getStandardizedItem($item, $pool));
            }
        );
    }


    /**
     * @inheritDoc
     */
    public function commit(): bool
    {
        return $this->makeOperation(
            static fn (ExtendedCacheItemPoolInterface $pool) => $pool->commit()
        );
    }
}
