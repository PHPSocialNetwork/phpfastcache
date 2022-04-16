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

namespace Phpfastcache\Drivers\Arangodb;

use ArangoDBClient\AdminHandler;
use ArangoDBClient\Collection as ArangoCollection;
use ArangoDBClient\CollectionHandler as ArangoCollectionHandler;
use ArangoDBClient\Connection as ArangoConnection;
use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;
use ArangoDBClient\Document as ArangoDocument;
use ArangoDBClient\DocumentHandler as ArangoDocumentHandler;
use ArangoDBClient\Exception as ArangoException;
use ArangoDBClient\ServerException as ArangoServerException;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * Class Driver
 * @method Config getConfig()
 * @property ArangoConnection $instance
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    protected const TTL_FIELD_NAME = 't';

    protected ArangoDocumentHandler $documentHandler;
    protected ArangoCollectionHandler $collectionHandler;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \class_exists(ArangoConnection::class);
    }

    /**
     * @return bool
     * @throws ArangoException
     * @throws PhpfastcacheDriverConnectException
     */
    protected function driverConnect(): bool
    {
        $connectionOptions = [
            ArangoConnectionOptions::OPTION_DATABASE => $this->getConfig()->getDatabase(),
            ArangoConnectionOptions::OPTION_ENDPOINT => $this->getConfig()->getEndpoint(),

            ArangoConnectionOptions::OPTION_CONNECTION  => $this->getConfig()->getConnection(),
            ArangoConnectionOptions::OPTION_AUTH_TYPE   => $this->getConfig()->getAuthType(),
            ArangoConnectionOptions::OPTION_AUTH_USER   => $this->getConfig()->getAuthUser(),
            ArangoConnectionOptions::OPTION_AUTH_PASSWD => $this->getConfig()->getAuthPasswd(),

            ArangoConnectionOptions::OPTION_CONNECT_TIMEOUT => $this->getConfig()->getConnectTimeout(),
            ArangoConnectionOptions::OPTION_REQUEST_TIMEOUT => $this->getConfig()->getRequestTimeout(),
            ArangoConnectionOptions::OPTION_CREATE        => $this->getConfig()->isAutoCreate(),
            ArangoConnectionOptions::OPTION_UPDATE_POLICY => $this->getConfig()->getUpdatePolicy(),

            // Options below are not yet supported
            // ConnectionOptions::OPTION_MEMCACHED_PERSISTENT_ID => 'arangodb-php-pool',
            // ConnectionOptions::OPTION_MEMCACHED_SERVERS       => [ [ '127.0.0.1', 11211 ] ],
            // ConnectionOptions::OPTION_MEMCACHED_OPTIONS       => [ ],
            // ConnectionOptions::OPTION_MEMCACHED_ENDPOINTS_KEY => 'arangodb-php-endpoints'
            // ConnectionOptions::OPTION_MEMCACHED_TTL           => 600
        ];

        if ($this->getConfig()->getTraceFunction() !== null) {
            $connectionOptions[ArangoConnectionOptions::OPTION_TRACE] = $this->getConfig()->getTraceFunction();
        }

        if ($this->getConfig()->getAuthJwt() !== null) {
            $connectionOptions[ArangoConnectionOptions::OPTION_AUTH_JWT] = $this->getConfig()->getAuthJwt();
        }

        if (\str_starts_with($this->getConfig()->getAuthType(), 'ssl://')) {
            $connectionOptions[ArangoConnectionOptions::OPTION_VERIFY_CERT] = $this->getConfig()->isVerifyCert();
            $connectionOptions[ArangoConnectionOptions::OPTION_ALLOW_SELF_SIGNED] = $this->getConfig()->isSelfSigned();
            $connectionOptions[ArangoConnectionOptions::OPTION_CIPHERS] = $this->getConfig()->getCiphers();
        }

        $this->eventManager->dispatch(Event::ARANGODB_CONNECTION, $this, new EventReferenceParameter($connectionOptions));

        $this->instance = new ArangoConnection($connectionOptions);
        $this->documentHandler = new ArangoDocumentHandler($this->instance);
        $this->collectionHandler = new ArangoCollectionHandler($this->instance);

        $collectionNames = array_keys($this->collectionHandler->getAllCollections());

        if ($this->getConfig()->isAutoCreate() && !\in_array($this->getConfig()->getCollection(), $collectionNames, true)) {
            return $this->createCollection($this->getConfig()->getCollection());
        }

        return $this->collectionHandler->has($this->getConfig()->getCollection());
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     * @throws PhpfastcacheDriverException
     * @throws \Exception
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        try {
            $document = $this->documentHandler->get($this->getConfig()->getCollection(), $item->getEncodedKey());
        } catch (ArangoServerException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw new PhpfastcacheDriverException(
                'Got unexpected error from Arangodb: ' . $e->getMessage()
            );
        }

        return $this->decodeDocument($document);
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws ArangoException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $options = [
            'overwriteMode' => 'replace',
            'returnNew' => true,
            'returnOld' => false,
            'silent' => false,
        ];

        $document = new ArangoDocument();
        $document->setInternalKey($item->getEncodedKey());
        $document->set(self::DRIVER_KEY_WRAPPER_INDEX, $item->getKey());
        $document->set(self::DRIVER_DATA_WRAPPER_INDEX, $this->encode($item->getRawValue()));
        $document->set(self::DRIVER_TAGS_WRAPPER_INDEX, $item->getTags());
        $document->set(self::DRIVER_EDATE_WRAPPER_INDEX, $item->getExpirationDate());
        $document->set(self::TTL_FIELD_NAME, $item->getExpirationDate()->getTimestamp());

        if ($this->getConfig()->isItemDetailedDate()) {
            $document->set(self::DRIVER_CDATE_WRAPPER_INDEX, $item->getCreationDate());
            $document->set(self::DRIVER_MDATE_WRAPPER_INDEX, $item->getModificationDate());
        }

        return $this->documentHandler->insert($this->getConfig()->getCollection(), $document, $options) !== null;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $options = [
            'returnOld' => false
        ];

        try {
            $this->documentHandler->removeById($this->getConfig()->getCollection(), $item->getEncodedKey(), null, $options);
            return true;
        } catch (ArangoException) {
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        try {
            $this->collectionHandler->truncate($this->getConfig()->getCollection());
            return true;
        } catch (ArangoException) {
            return false;
        }
    }

    /**
     * @throws PhpfastcacheDriverConnectException
     * @throws ArangoException
     */
    protected function createCollection(string $collectionName): bool
    {
        $collection = new ArangoCollection($collectionName);

        try {
            $params = [
                'type' => ArangoCollection::TYPE_DOCUMENT,
                'waitForSync' => false
            ];

            $this->eventManager->dispatch(Event::ARANGODB_COLLECTION_PARAMS, $this, new EventReferenceParameter($params));

            $this->collectionHandler->create($collection, $params);

            $this->collectionHandler->createIndex($collection, [
                'type'         => 'ttl',
                'name'         => 'expires_at',
                'fields'       => [self::TTL_FIELD_NAME],
                'unique'       => false,
                'sparse'       => true,
                'inBackground' => true,
                'expireAfter' => 1
            ]);
            return true;
        } catch (\Throwable $e) {
            throw new PhpfastcacheDriverConnectException(
                sprintf(
                    'Unable to automatically create the collection, error returned from ArangoDB: [%d] %s',
                    $e->getCode(),
                    $e->getMessage(),
                )
            );
        }
    }

    /**
     * @param ArangoDocument $document
     * @return array<string, mixed>
     * @throws \Exception
     */
    protected function decodeDocument(ArangoDocument $document): array
    {
        $value = [
            self::DRIVER_KEY_WRAPPER_INDEX => $document->get(self::DRIVER_KEY_WRAPPER_INDEX),
            self::DRIVER_TAGS_WRAPPER_INDEX => $document->get(self::DRIVER_TAGS_WRAPPER_INDEX),
            self::DRIVER_DATA_WRAPPER_INDEX => $this->decode(
                $document->get(self::DRIVER_DATA_WRAPPER_INDEX),
            ),
        ];

        $eDate = $document->get(self::DRIVER_EDATE_WRAPPER_INDEX);
        $value[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX] = new \DateTime(
            $eDate['date'],
            new \DateTimeZone($eDate['timezone'])
        );

        if ($this->getConfig()->isItemDetailedDate()) {
            $cDate = $document->get(self::DRIVER_CDATE_WRAPPER_INDEX);
            if (isset($cDate['date'], $cDate['timezone'])) {
                $value[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] = new \DateTime(
                    $cDate['date'],
                    new \DateTimeZone($cDate['timezone'])
                );
            }

            $mDate = $document->get(self::DRIVER_MDATE_WRAPPER_INDEX);
            if (isset($mDate['date'], $cDate['timezone'])) {
                $value[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] = new \DateTime(
                    $mDate['date'],
                    new \DateTimeZone($mDate['timezone'])
                );
            }
        }

        return $value;
    }

    public function getStats(): DriverStatistic
    {
        $rawData = [];

        $rawData['collectionCount'] = $this->collectionHandler->count($this->getConfig()->getCollection(), false);
        $rawData['collectionInfo'] = $this->collectionHandler->get($this->getConfig()->getCollection());

        try {
            $adminHandler = new AdminHandler($this->instance);
            $rawData['adminInfo'] = $adminHandler->getServerVersion(true);
            $infoText = \sprintf(
                '%s server v%s "%s" edition (%s/%s).',
                \ucfirst($rawData['adminInfo']['server']),
                $rawData['adminInfo']['version'] ?? 'unknown version',
                $rawData['adminInfo']['license'] ?? 'unknown licence',
                $rawData['adminInfo']['details']['architecture'] ?? 'unknown architecture',
                $rawData['adminInfo']['details']['platform'] ?? 'unknown platform',
            );
        } catch (ArangoException $e) {
            $infoText = 'No readable human data, encountered an error while trying to get details: ' . $e->getMessage();
        }

        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo($infoText)
            ->setRawData($rawData)
            ->setSize($rawData['collectionCount']);
    }
}
