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

namespace Phpfastcache\Drivers\Predis;

use DateTime;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Predis\Client as PredisClient;
use Predis\Connection\ConnectionException as PredisConnectionException;

/**
 * @property PredisClient $instance Instance of driver service
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        if (extension_loaded('Redis')) {
            trigger_error('The native Redis extension is installed, you should use Redis instead of Predis to increase performances', E_USER_NOTICE);
        }

        return class_exists(\Predis\Client::class);
    }

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
        $size = (isset($info['Memory']['used_memory']) ? $info['Memory']['used_memory'] : 0);
        $version = (isset($info['Server']['redis_version']) ? $info['Server']['redis_version'] : 0);
        $date = (isset($info['Server']['uptime_in_seconds']) ? (new DateTime())->setTimestamp(time() - $info['Server']['uptime_in_seconds']) : 'unknown date');

        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData($info)
            ->setSize((int)$size)
            ->setInfo(
                sprintf(
                    "The Redis daemon v%s is up since %s.\n For more information see RawData. \n Driver size includes the memory allocation size.",
                    $version,
                    $date->format(DATE_RFC2822)
                )
            );
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheLogicException
     */
    protected function driverConnect(): bool
    {
        /**
         * In case of an user-provided
         * Predis client just return here
         */
        if ($this->getConfig()->getPredisClient() instanceof PredisClient) {
            $this->instance = $this->getConfig()->getPredisClient();
            if (!$this->instance->isConnected()) {
                $this->instance->connect();
            }
            return true;
        }

        $options = [];

        if ($this->getConfig()->getOptPrefix()) {
            $options['prefix'] = $this->getConfig()->getOptPrefix();
        }

        if (!empty($this->getConfig()->getPath())) {
            $this->instance = new PredisClient(
                [
                    'scheme' => $this->getConfig()->getScheme(),
                    'persistent' => $this->getConfig()->isPersistent(),
                    'timeout' => $this->getConfig()->getTimeout(),
                    'path' => $this->getConfig()->getPath(),
                ],
                $options
            );
        } else {
            $this->instance = new PredisClient($this->getConfig()->getPredisConfigArray(), $options);
        }

        try {
            $this->instance->connect();
        } catch (PredisConnectionException $e) {
            throw new PhpfastcacheDriverException(
                'Failed to connect to predis server. Check the Predis documentation: https://github.com/nrk/predis/tree/v1.1#how-to-install-and-use-predis',
                0,
                $e
            );
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

        if ($val === null) {
            return null;
        }

        return $this->decode($val);
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

        /**
         * @see https://redis.io/commands/setex
         * @see https://redis.io/commands/expire
         */
        if ($ttl <= 0) {
            return (bool)$this->instance->expire($item->getKey(), 0);
        }

        return $this->instance->setex($item->getKey(), $ttl, $this->encode($this->driverPreWrap($item)))->getPayload() === 'OK';
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        return (bool)$this->instance->del([$item->getKey()]);
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->instance->flushdb()->getPayload() === 'OK';
    }
}
