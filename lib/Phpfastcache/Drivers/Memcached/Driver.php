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

namespace Phpfastcache\Drivers\Memcached;

use DateTime;
use Exception;
use Memcached as MemcachedSoftware;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Util\MemcacheDriverCollisionDetectorTrait;
use Psr\Cache\CacheItemInterface;

/**
 * @property MemcachedSoftware $instance
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait {
        __construct as protected __parentConstruct;
    }
    use MemcacheDriverCollisionDetectorTrait;

    /**
     * Driver constructor.
     * @param ConfigurationOption $config
     * @param string $instanceId
     * @param EventManagerInterface $em
     * @throws PhpfastcacheDriverConnectException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheIOException
     */
    public function __construct(ConfigurationOption $config, string $instanceId, EventManagerInterface $em)
    {
        self::checkCollision('Memcached');
        $this->__parentConstruct($config, $instanceId, $em);
    }

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return class_exists('Memcached');
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $stats = current($this->instance->getStats());
        $stats['uptime'] = $stats['uptime'] ?? 0;
        $stats['bytes'] = $stats['bytes'] ?? 0;
        $stats['version'] = $stats['version'] ?? $this->instance->getVersion();

        $date = (new DateTime())->setTimestamp(time() - $stats['uptime']);

        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo(sprintf("The memcache daemon v%s is up since %s.\n For more information see RawData.", $stats['version'], $date->format(DATE_RFC2822)))
            ->setRawData($stats)
            ->setSize((int)$stats['bytes']);
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverException
     */
    protected function driverConnect(): bool
    {
        $this->instance = new MemcachedSoftware();
        $optPrefix = $this->getConfig()->getOptPrefix();
        $this->instance->setOption(MemcachedSoftware::OPT_BINARY_PROTOCOL, true);

        if ($optPrefix) {
            $this->instance->setOption(MemcachedSoftware::OPT_PREFIX_KEY, $optPrefix);
        }

        foreach ($this->getConfig()->getServers() as $server) {
            $connected = false;
            /**
             * If path is provided we consider it as an UNIX Socket
             */
            if (!empty($server['path'])) {
                $connected = $this->instance->addServer($server['path'], 0);
            } elseif (!empty($server['host'])) {
                $connected = $this->instance->addServer($server['host'], $server['port']);
            }
            if (!empty($server['saslUser']) && !empty($server['saslPassword'])) {
                $this->instance->setSaslAuthData($server['saslUser'], $server['saslPassword']);
            }
            if (!$connected) {
                throw new PhpfastcacheDriverConnectException(
                    sprintf(
                        'Failed to connect to memcache host/path "%s".',
                        $server['host'] ?: $server['path'],
                    )
                );
            }
        }

        /**
         * Since Memcached does not throw
         * any error if not connected ...
         */
        $version = $this->instance->getVersion();
        if (!$version || $this->instance->getResultCode() !== MemcachedSoftware::RES_SUCCESS) {
            throw new PhpfastcacheDriverException('Memcached seems to not be connected');
        }
        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $val = $this->instance->get($item->getKey());

        if (empty($val) || !\is_array($val)) {
            return null;
        }

        return $val;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        $ttl = $item->getExpirationDate()->getTimestamp() - time();

        // Memcache will only allow a expiration timer less than 2592000 seconds,
        // otherwise, it will assume you're giving it a UNIX timestamp.
        if ($ttl > 2592000) {
            $ttl = time() + $ttl;
        }

        return $this->instance->set($item->getKey(), $this->driverPreWrap($item), $ttl);
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return $this->instance->delete($item->getKey());
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->instance->flush();
    }
}
