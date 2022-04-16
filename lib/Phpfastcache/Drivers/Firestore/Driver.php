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

use Google\Cloud\Core\Blob as GoogleBlob;
use Google\Cloud\Core\Timestamp as GoogleTimestamp;
use Google\Cloud\Firestore\FirestoreClient as GoogleFirestoreClient;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * Class Driver
 * @method Config getConfig()
 * @property GoogleFirestoreClient $instance
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \class_exists(GoogleFirestoreClient::class) && \extension_loaded('grpc');
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverConnectException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverConnect(): bool
    {
        $gcpId = $this->getConfig()->getSuperGlobalAccessor()('SERVER', 'GOOGLE_CLOUD_PROJECT');
        $gacPath = $this->getConfig()->getSuperGlobalAccessor()('SERVER', 'GOOGLE_APPLICATION_CREDENTIALS');

        if (empty($gcpId)) {
            throw new PhpfastcacheDriverConnectException('The environment configuration GOOGLE_CLOUD_PROJECT must be set');
        }

        if (empty($gacPath) || !\is_readable($gacPath)) {
            throw new PhpfastcacheDriverConnectException('The environment configuration GOOGLE_APPLICATION_CREDENTIALS must be set and the JSON file must be readable.');
        }

        $this->instance = new GoogleFirestoreClient();

        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->instance->collection($this->getConfig()->getCollection())
            ->document($item->getKey())
            ->set(
                $this->driverPreWrap($item),
                ['merge' => true]
            );

        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     * @throws \Exception
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $doc = $this->instance->collection($this->getConfig()->getCollection())
            ->document($item->getKey());

        $snapshotData = $doc->snapshot()->data();

        if (\is_array($snapshotData)) {
            return $this->decodeFirestoreDocument($snapshotData);
        }

        return null;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->instance->collection($this->getConfig()->getCollection())
            ->document($item->getKey())
            ->delete();

        return true;
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        $batchSize = 100;
        $collection = $this->instance->collection($this->getConfig()->getCollection());
        $documents = $collection->limit($batchSize)->documents();
        while (!$documents->isEmpty()) {
            foreach ($documents as $document) {
                $document->reference()->delete();
            }
            $documents = $collection->limit($batchSize)->documents();
        }

        return true;
    }

    /**
     * @param array<string, mixed> $snapshotData
     * @return array<string, mixed>
     */
    protected function decodeFirestoreDocument(array $snapshotData): array
    {
        return \array_map(static function ($datum) {
            if ($datum instanceof GoogleTimestamp) {
                $date = $datum->get();
                if ($date instanceof \DateTimeImmutable) {
                    return \DateTime::createFromImmutable($date);
                }
                return $date;
            }

            if ($datum instanceof GoogleBlob) {
                return (string) $datum;
            }

            return $datum;
        }, $snapshotData);
    }

    public function getStats(): DriverStatistic
    {
        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo('No info provided by Google Firestore')
            ->setRawData([])
            ->setSize(0);
    }
}
