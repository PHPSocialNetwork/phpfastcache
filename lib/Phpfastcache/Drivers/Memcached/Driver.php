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
use Phpfastcache\Core\Pool\{ExtendedCacheItemPoolInterface, TaggableCacheItemPoolTrait};
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{PhpfastcacheDriverException, PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException};
use Phpfastcache\Util\{MemcacheDriverCollisionDetectorTrait};
use Psr\Cache\CacheItemInterface;


/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property MemcachedSoftware $instance
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait {
        __construct as protected __parentConstruct;
    }
    use MemcacheDriverCollisionDetectorTrait;

    /**
     * Driver constructor.
     * @param ConfigurationOption $config
     * @param string $instanceId
     * @throws PhpfastcacheDriverException
     */
    public function __construct(ConfigurationOption $config, string $instanceId)
    {
        self::checkCollision('Memcached');
        $this->__parentConstruct($config, $instanceId);
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

        $servers = $this->getConfig()->getServers();

        if (count($servers) < 1) {
            $servers = [
                [
                    'host' => $this->getConfig()->getHost(),
                    'path' => $this->getConfig()->getPath(),
                    'port' => $this->getConfig()->getPort(),
                    'saslUser' => $this->getConfig()->getSaslUser() ?: false,
                    'saslPassword' => $this->getConfig()->getSaslPassword() ?: false,
                ],
            ];
        }

        foreach ($servers as $server) {
            try {
                /**
                 * If path is provided we consider it as an UNIX Socket
                 */
                if (!empty($server['path']) && !$this->instance->addServer($server['path'], 0)) {
                    $this->fallback = true;
                } else {
                    if (!empty($server['host']) && !$this->instance->addServer($server['host'], $server['port'])) {
                        $this->fallback = true;
                    }
                }

                if (!empty($server['saslUser']) && !empty($server['saslPassword'])) {
                    $this->instance->setSaslAuthData($server['saslUser'], $server['saslPassword']);
                }
            } catch (Exception $e) {
                $this->fallback = true;
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
     * @return null|array
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $val = $this->instance->get($item->getKey());

        if ($val === false) {
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

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->instance->flush();
    }
}
