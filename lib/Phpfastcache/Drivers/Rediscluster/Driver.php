<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

declare(strict_types=1);

namespace Phpfastcache\Drivers\Rediscluster;

use DateTime;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Drivers\Redis\RedisDriverTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use RedisCluster;

/**
 * @property RedisCluster $instance
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use RedisDriverTrait, TaggableCacheItemPoolTrait {
        RedisDriverTrait::driverReadMultiple insteadof TaggableCacheItemPoolTrait;
        RedisDriverTrait::driverDeleteMultiple insteadof TaggableCacheItemPoolTrait;
    }

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('Redis') && class_exists(RedisCluster::class);
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $masters = $this->instance->_masters();
        $infos = array_map(fn($cluster) => $this->instance->info($cluster), $masters);
        $date = (new DateTime())->setTimestamp(time() - min(array_column($infos, 'uptime_in_seconds')));

        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData($infos)
            ->setSize(array_sum(array_column($infos, 'used_memory_dataset')))
            ->setInfo(
                sprintf(
                    trim(<<<EOF
                        Redis Cluster version v%s, php-ext v%s with %d master nodes and %d slaves connected are up since %s.
                        For more information see RawData.
                    EOF),
                    implode(', ', array_unique(array_column($infos, 'redis_version'))),
                    \phpversion("redis"),
                    count($masters),
                    array_sum(array_column($infos, 'connected_slaves')),
                    $date->format(DATE_RFC2822)
                )
            );
    }

    /**
     * @return bool
     * @throws PhpfastcacheLogicException
     */
    protected function driverConnect(): bool
    {
        if (isset($this->instance) && $this->instance instanceof RedisCluster) {
            throw new PhpfastcacheLogicException('Already connected to Redis server');
        }

        /**
         * In case of a user-provided
         * Redis client just return here
         */
        if ($this->getConfig()->getRedisClusterClient() instanceof RedisCluster) {
            /**
             * Unlike Predis, we can't test if we're connected
             * or not, so let's just assume that we are
             */
            $this->instance = $this->getConfig()->getRedisClusterClient();
            return true;
        }

        $this->instance = $this->instance ?? new RedisCluster(
            null,
            $this->getConfig()->getClusters(),
            $this->getConfig()->getTimeout(),
            $this->getConfig()->getReadTimeout(),
            true,
            $this->getConfig()->getPassword()
        );

        $this->instance->setOption(RedisCluster::OPT_SCAN, RedisCluster::SCAN_RETRY);

        if ($this->getConfig()->getOptPrefix()) {
            $this->instance->setOption(RedisCluster::OPT_PREFIX, $this->getConfig()->getOptPrefix());
        }

        if ($this->getConfig()->getSlaveFailover()) {
            $this->instance->setOption(RedisCluster::OPT_SLAVE_FAILOVER, $this->getConfig()->getSlaveFailover());
        }


        return true;
    }

    /**
     * @return array<int, string>
     */
    protected function driverReadAllKeys(string $pattern = '*'): iterable
    {
        $keys = [[]];
        foreach ($this->instance->_masters() as $master) {
            $i = -1;
            $result = $this->instance->scan(
                $i,
                $master,
                $pattern === '' ? '*' : $pattern,
                ExtendedCacheItemPoolInterface::MAX_ALL_KEYS_COUNT
            );
            if (is_array($result)) {
                $keys[] = $result;
            }
        }

        return array_unique(array_merge(...$keys));
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        foreach ($this->instance->_masters() as $nodeMaster) {
            $this->instance->flushDb($nodeMaster);
        }
        return true;
    }
}
