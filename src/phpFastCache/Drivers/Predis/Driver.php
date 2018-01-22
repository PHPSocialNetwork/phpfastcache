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

namespace phpFastCache\Drivers\Predis;

use phpFastCache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\{
  phpFastCacheInvalidArgumentException, phpFastCacheDriverException
};
use phpFastCache\Util\ArrayObject;
use Predis\Client as PredisClient;
use Predis\Connection\ConnectionException as PredisConnectionException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property PredisClient $instance Instance of driver service
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        if (extension_loaded('Redis')) {
            trigger_error('The native Redis extension is installed, you should use Redis instead of Predis to increase performances', E_USER_NOTICE);
        }

        return class_exists('Predis\Client');
    }

    /**
     * @return bool
     * @throws phpFastCacheDriverException
     */
    protected function driverConnect(): bool
    {
        $config = $this->getConfig();
        if(!empty($config['path'])){
            $this->instance = new PredisClient([
              'scheme' => 'unix',
              'path' =>  $config['path']
            ]);
        }else{
            $this->instance = new PredisClient($this->getConfig());
        }

        try {
            $this->instance->connect();
        } catch (PredisConnectionException $e) {
            throw new phpFastCacheDriverException('Failed to connect to predis server. Check the Predis documentation: https://github.com/nrk/predis/tree/v1.1#how-to-install-and-use-predis', 0, $e);
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
        if ($val == false) {
            return null;
        } else {
            return $this->decode($val);
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $ttl = $item->getExpirationDate()->getTimestamp() - time();

            /**
             * @see https://redis.io/commands/setex
             * @see https://redis.io/commands/expire
             */
            if ($ttl <= 0) {
                return (bool)$this->instance->expire($item->getKey(), 0);
            } else {
                return $this->instance->setex($item->getKey(), $ttl, $this->encode($this->driverPreWrap($item)))->getPayload() === 'OK';
            }
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return (bool) $this->instance->del([$item->getKey()]);
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->instance->flushdb()->getPayload() === 'OK';
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/


    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
<p>
To install the Predis library via Composer:
<code>composer require "predis/predis" "~1.1.0"</code>
</p>
HELP;
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $info = $this->instance->info();
        $size = (isset($info[ 'Memory' ][ 'used_memory' ]) ? $info[ 'Memory' ][ 'used_memory' ] : 0);
        $version = (isset($info[ 'Server' ][ 'redis_version' ]) ? $info[ 'Server' ][ 'redis_version' ] : 0);
        $date = (isset($info[ 'Server' ][ 'uptime_in_seconds' ]) ? (new \DateTime())->setTimestamp(time() - $info[ 'Server' ][ 'uptime_in_seconds' ]) : 'unknown date');

        return (new DriverStatistic())
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setRawData($info)
          ->setSize((int) $size)
          ->setInfo(sprintf("The Redis daemon v%s is up since %s.\n For more information see RawData. \n Driver size includes the memory allocation size.",
            $version, $date->format(DATE_RFC2822)));
    }

    /**
     * @return ArrayObject
     */
    public function getDefaultConfig(): ArrayObject
    {
        $defaultConfig = new ArrayObject();

        $defaultConfig[ 'host' ] = '127.0.0.1';
        $defaultConfig[ 'path' ] = false;
        $defaultConfig[ 'port' ] = 6379;
        $defaultConfig[ 'password' ] = null;
        $defaultConfig[ 'database' ] = null;

        return $defaultConfig;
    }
}