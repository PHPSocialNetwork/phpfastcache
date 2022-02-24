<?php

/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Memcached;

use DateTimeImmutable;
use Memcached as MemcachedSoftware;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
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

/**
 * @property MemcachedSoftware $instance
 * @property Config $config Return the config object
 */
class Driver implements AggregatablePoolInterface, ExtendedCacheItemPoolInterface
{
    use MemcacheDriverCollisionDetectorTrait;
    use TaggableCacheItemPoolTrait {
        __construct as protected __parentConstruct;
    }

    /**
     * Driver constructor.
     *
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

    public function driverCheck(): bool
    {
        return class_exists('Memcached');
    }

    public function getStats(): DriverStatistic
    {
        $stats = current($this->instance->getStats());
        $stats['uptime'] ??= 0;
        $stats['bytes'] ??= 0;
        $stats['version'] ??= $this->instance->getVersion();

        $date = (new DateTimeImmutable())->setTimestamp(time() - $stats['uptime']);

        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo(sprintf("The memcache daemon v%s is up since %s.\n For more information see RawData.", $stats['version'], $date->format(\DATE_RFC2822)))
            ->setRawData($stats)
            ->setSize((int) $stats['bytes']);
    }

    /**
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

        $servers = $this->getConfig()->getServers();

        foreach ($servers as $server) {
            $connected = false;
            /*
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
                throw new PhpfastcacheDriverConnectException(sprintf('Failed to connect to memcache host/path "%s".', $server['host'] ?: $server['path'], ));
            }
        }

        /**
         * Since Memcached does not throw
         * any error if not connected ...
         */
        $version = $this->instance->getVersion();
        if (!$version || MemcachedSoftware::RES_SUCCESS !== $this->instance->getResultCode()) {
            throw new PhpfastcacheDriverException('Memcached seems to not be connected');
        }

        return true;
    }

    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $val = $this->instance->get($item->getKey());

        if (false === $val) {
            return null;
        }

        return $val;
    }

    /**
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
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return $this->instance->delete($item->getKey());
    }

    protected function driverClear(): bool
    {
        return $this->instance->flush();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
