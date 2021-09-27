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

namespace Phpfastcache\Drivers\Firestore;

use Google\Cloud\Firestore\FirestoreClient as GoogleFirestoreClient;

use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;

/**
 * Class Driver
 * @property Config $config
 * @property GoogleFirestoreClient $instance
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    protected const TTL_FIELD_NAME = 't';

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \class_exists(GoogleFirestoreClient::class) && \extension_loaded('grpc');
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
/*
        $this->instance = new GoogleFirestoreClient([
            //'projectId' => $projectId,
        ]);*/

/*        if (!$this->hasCollection()) {
            $this->createCollection();
        }

        if (!$this->hasTtlEnabled()) {
            $this->enableTtl();
        }*/

        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return null|array
     * @throws \Exception
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        return null;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return true;
    }

    protected function hasCollection(): bool
    {
        return true;
    }

    protected function createCollection() :void
    {
    }

    protected function hasTtlEnabled(): bool
    {
        return true;
    }

    protected function enableTtl(): void
    {
    }

    public function getStats(): DriverStatistic
    {
        return new DriverStatistic();
    }

    protected function encodeDocument(array $data): array
    {
        $data[self::DRIVER_DATA_WRAPPER_INDEX] = $this->encode($data[self::DRIVER_DATA_WRAPPER_INDEX]);

        return $data;
    }

    protected function decodeDocument(array $data): array
    {
        $data[self::DRIVER_DATA_WRAPPER_INDEX] = $this->decode($data[self::DRIVER_DATA_WRAPPER_INDEX]);

        return $data;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
