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
use Google\Cloud\Firestore\DocumentSnapshot;
use Google\Cloud\Firestore\FirestoreClient as GoogleFirestoreClient;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
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

    public const MINIMUM_FIRESTORE_CLIENT_VERSION = '1.35.0';

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        if (!\class_exists(GoogleFirestoreClient::class) || !\extension_loaded('grpc')) {
            return false;
        }

        if (!version_compare(GoogleFirestoreClient::VERSION, self::MINIMUM_FIRESTORE_CLIENT_VERSION, '>=')) {
            throw new PhpfastcacheDriverCheckException(
                sprintf(
                    'Firestore client version must be at least %s or greater.',
                    self::MINIMUM_FIRESTORE_CLIENT_VERSION
                )
            );
        }
        return true;
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverConnectException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverConnect(): bool
    {
        if ($this->getConfig()->getFirestoreClient()) {
            $this->instance = $this->getConfig()->getFirestoreClient();
        } else {
            $gcpId = $this->getConfig()->getSuperGlobalAccessor()('SERVER', 'GOOGLE_CLOUD_PROJECT');
            $gacPath = $this->getConfig()->getSuperGlobalAccessor()('SERVER', 'GOOGLE_APPLICATION_CREDENTIALS');

            if (empty($gcpId)) {
                throw new PhpfastcacheDriverConnectException('The environment configuration GOOGLE_CLOUD_PROJECT must be set');
            }

            if (empty($gacPath) || !\is_readable($gacPath)) {
                throw new PhpfastcacheDriverConnectException(
                    'The environment configuration GOOGLE_APPLICATION_CREDENTIALS must be set and the JSON file must be readable.'
                );
            }

            $options = ['database' => $this->getConfig()->getDatabaseName()];

            $this->eventManager->dispatch(Event::FIRESTORE_CLIENT_OPTIONS, $this, new EventReferenceParameter($options));

            $this->instance = new GoogleFirestoreClient($options);
        }

        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->instance->collection($this->getConfig()->getCollectionName())
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
        $doc = $this->instance->collection($this->getConfig()->getCollectionName())
            ->document($item->getKey());

        $snapshotData = $doc->snapshot()->data();

        if (\is_array($snapshotData)) {
            return $this->decodeFirestoreDocument($snapshotData);
        }

        return null;
    }

    /**
     * @return array<int, string>
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverReadAllKeys(string $pattern = ''): iterable
    {
        if ($pattern !== '') {
            throw new PhpfastcacheInvalidArgumentException('Firestore does not support a pattern argument');
        }
        $data = [];
        $documents = $this->instance->collection($this->getConfig()->getCollectionName())
            ->limit(ExtendedCacheItemPoolInterface::MAX_ALL_KEYS_COUNT)
            ->documents();

        /** @var DocumentSnapshot[] $documents */
        foreach ($documents as $document) {
            $data[] = $document->id();
        }

        return $data;
    }

    /**
     * @param ExtendedCacheItemInterface ...$items
     * @return array<array<string, mixed>>
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
     * @throws \RedisException
     */
    protected function driverReadMultiple(ExtendedCacheItemInterface ...$items): array
    {
        $data = [];
        $keys = $this->getKeys($items);
        $documents = $this->instance->collection($this->getConfig()->getCollectionName())
            ->limit(ExtendedCacheItemPoolInterface::MAX_ALL_KEYS_COUNT)
            ->where(ExtendedCacheItemPoolInterface::DRIVER_KEY_WRAPPER_INDEX, 'in', $keys)
            ->documents();

        /** @var DocumentSnapshot[] $documents */
        foreach ($documents as $document) {
            $data[$document->id()] = $this->decodeFirestoreDocument($document->data());
        }

        return $data;
    }

    /**
     * @param string $key
     * @param string $encodedKey
     * @return bool
     */
    protected function driverDelete(string $key, string $encodedKey): bool
    {
        $this->instance->collection($this->getConfig()->getCollectionName())
            ->document($key)
            ->delete();

        return true;
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        $batchSize = $this->getConfig()->getBatchSize();
        $collection = $this->instance->collection($this->getConfig()->getCollectionName());
        do {
            $documents = $collection->limit($batchSize)->documents();
            /** @var DocumentSnapshot $document */
            foreach ($documents as $document) {
                $document->reference()->delete();
            }
        } while (!$documents->isEmpty());

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
        $info = sprintf(
            'Firestore client v%s, collection "%s". No additional info provided by Google Firestore',
            defined($this->instance::class . '::VERSION') ? $this->instance::VERSION : '[unknown version]',
            $this->getConfig()->getCollectionName(),
        );
        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo($info)
            ->setRawData([])
            ->setSize(0);
    }
}
