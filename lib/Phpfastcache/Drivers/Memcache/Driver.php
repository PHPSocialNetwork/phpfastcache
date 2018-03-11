<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Memcache;

use Memcache as MemcacheSoftware;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{
  PhpfastcacheInvalidArgumentException, PhpfastcacheDriverException
};
use Phpfastcache\Util\{ArrayObject, MemcacheDriverCollisionDetectorTrait};
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property MemcacheSoftware $instance
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait {
        __construct as protected __parentConstruct;
    }
    use MemcacheDriverCollisionDetectorTrait;

    /**
     * @var int
     */
    protected $memcacheFlags = 0;

    /**
     * Driver constructor.
     * @param ConfigurationOption $config
     * @param string $instanceId
     * @throws PhpfastcacheDriverException
     */
    public function __construct(ConfigurationOption $config, string $instanceId)
    {
        self::checkCollision('Memcache');
        $this->__parentConstruct($config, $instanceId);
    }

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \class_exists('Memcache');
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        $this->instance = new MemcacheSoftware();
        $servers = (!empty($this->config->getOption('servers')) && \is_array($this->config->getOption('servers')) ? $this->config->getOption('servers') : []);
        if (\count($servers) < 1) {
            $servers = [
              [
                'host' => !empty($this->config->getOption('host')) ? $this->config->getOption('host') : '127.0.0.1',
                'path' => !empty($this->config->getOption('path')) ? $this->config->getOption('path') : false,
                'port' => !empty($this->config->getOption('port')) ? $this->config->getOption('port') : 11211,
                'saslUser' => !empty($this->config->getOption('saslUser')) ? $this->config->getOption('saslUser') : false,
                'saslPassword' =>!empty($this->config->getOption('saslPassword')) ? $this->config->getOption('saslPassword'): false,
              ],
            ];
        }

        foreach ($servers as $server) {
            try {
                /**
                 * If path is provided we consider it as an UNIX Socket
                 */
                if(!empty($server[ 'path' ]) && !$this->instance->addServer($server[ 'path' ], 0)){
                    $this->fallback = true;
                }else if (!empty($server[ 'host' ]) && !$this->instance->addServer($server[ 'host' ], $server[ 'port' ])) {
                    $this->fallback = true;
                }

                if (!empty($server[ 'saslUser' ]) && !empty($server[ 'saslPassword' ])) {
                    throw new PhpfastcacheDriverException('Unlike Memcached, Memcache does not support SASL authentication');
                }
            } catch (\Exception $e) {
                $this->fallback = true;
            }

            /**
             * Since Memcached does not throw
             * any error if not connected ...
             */
            if(!$this->instance->getServerStatus(!empty($server[ 'path' ]) ? $server[ 'path' ] : $server[ 'host' ], !empty($server[ 'port' ]) ? $server[ 'port' ] : 0)){
                throw new PhpfastcacheDriverException('Memcache seems to not be connected');
            }
        }

        return true;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $val = $this->instance->get($item->getKey());

        if ($val === false) {
            return null;
        }

        return $val;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $ttl = $item->getExpirationDate()->getTimestamp() - time();

            // Memcache will only allow a expiration timer less than 2592000 seconds,
            // otherwise, it will assume you're giving it a UNIX timestamp.
            if ($ttl > 2592000) {
                $ttl = time() + $ttl;
            }
            return $this->instance->set($item->getKey(), $this->driverPreWrap($item), $this->memcacheFlags, $ttl);
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return $this->instance->delete($item->getKey());
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->instance->flush();
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $stats = (array)$this->instance->getstats();
        $stats[ 'uptime' ] = (isset($stats[ 'uptime' ]) ? $stats[ 'uptime' ] : 0);
        $stats[ 'version' ] = (isset($stats[ 'version' ]) ? $stats[ 'version' ] : 'UnknownVersion');
        $stats[ 'bytes' ] = (isset($stats[ 'bytes' ]) ? $stats[ 'version' ] : 0);

        $date = (new \DateTime())->setTimestamp(time() - $stats[ 'uptime' ]);

        return (new DriverStatistic())
          ->setData(\implode(', ', \array_keys($this->itemInstances)))
          ->setInfo(\sprintf("The memcache daemon v%s is up since %s.\n For more information see RawData.", $stats[ 'version' ], $date->format(DATE_RFC2822)))
          ->setRawData($stats)
          ->setSize((int)$stats[ 'bytes' ]);
    }
}