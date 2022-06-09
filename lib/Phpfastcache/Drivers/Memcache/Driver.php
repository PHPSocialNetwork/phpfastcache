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

namespace Phpfastcache\Drivers\Memcache;

use DateTime;
use Exception;
use Memcache as MemcacheSoftware;
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

/**
 * @property MemcacheSoftware $instance
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait {
        __construct as protected __parentConstruct;
    }
    use MemcacheDriverCollisionDetectorTrait;

    protected int $memcacheFlags = 0;

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
        self::checkCollision('Memcache');
        $this->__parentConstruct($config, $instanceId, $em);
    }

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return class_exists('Memcache');
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $stats = (array)$this->instance->getstats();
        $stats['uptime'] = (isset($stats['uptime']) ? $stats['uptime'] : 0);
        $stats['version'] = (isset($stats['version']) ? $stats['version'] : 'UnknownVersion');
        $stats['bytes'] = (isset($stats['bytes']) ? $stats['version'] : 0);

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
        $this->instance = new MemcacheSoftware();

        foreach ($this->getConfig()->getServers() as $server) {
            try {
                /**
                 * If path is provided we consider it as a UNIX Socket
                 */
                if (!empty($server['path'])) {
                    $this->instance->addServer($server['path'], 0);
                } elseif (!empty($server['host'])) {
                    $this->instance->addServer($server['host'], $server['port']);
                }

                if (!empty($server['saslUser']) && !empty($server['saslPassword'])) {
                    throw new PhpfastcacheDriverException('Unlike Memcached, Memcache does not support SASL authentication');
                }
            } catch (Exception $e) {
                throw new PhpfastcacheDriverConnectException(
                    sprintf(
                        'Failed to connect to memcache host/path "%s" with the following error: %s',
                        $server['host'] ?: $server['path'],
                        $e->getMessage()
                    )
                );
            }

            /**
             * Since Memcached does not throw
             * any error if not connected ...
             */
            if (
                !$this->instance->getServerStatus(
                    !empty($server['path']) ? $server['path'] : $server['host'],
                    !empty($server['port']) ? $server['port'] : 0
                )
            ) {
                throw new PhpfastcacheDriverException('Memcache seems to not be connected');
            }
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
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        $ttl = $item->getExpirationDate()->getTimestamp() - time();

        // Memcache will only allow an expiration timer less than 2592000 seconds,
        // otherwise, it will assume you're giving it a UNIX timestamp.
        if ($ttl > 2592000) {
            $ttl = time() + $ttl;
        }
        return $this->instance->set($item->getKey(), $this->driverPreWrap($item), $this->memcacheFlags, $ttl);
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
