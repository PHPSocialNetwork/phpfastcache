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
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * Class Driver
 * @property Config $config
 * @property ArangoConnection $instance
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
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
        /** @var Config $config */
        $config = $this->getConfig();

        $connectionOptions = [
            ArangoConnectionOptions::OPTION_DATABASE => $config->getDatabase(),
            ArangoConnectionOptions::OPTION_ENDPOINT => $config->getEndpoint(),

            ArangoConnectionOptions::OPTION_CONNECTION  => $config->getConnection(),
            ArangoConnectionOptions::OPTION_AUTH_TYPE   => $config->getAuthType(),
            ArangoConnectionOptions::OPTION_AUTH_USER   => $config->getAuthUser(),
            ArangoConnectionOptions::OPTION_AUTH_PASSWD => $config->getAuthPasswd(),

            ArangoConnectionOptions::OPTION_CONNECT_TIMEOUT => $config->getConnectTimeout(),
            ArangoConnectionOptions::OPTION_REQUEST_TIMEOUT => $config->getRequestTimeout(),
            ArangoConnectionOptions::OPTION_CREATE        => $config->isAutoCreate(),
            ArangoConnectionOptions::OPTION_UPDATE_POLICY => $config->getUpdatePolicy(),

            // Options below are not yet supported
            // ConnectionOptions::OPTION_MEMCACHED_PERSISTENT_ID => 'arangodb-php-pool',
            // ConnectionOptions::OPTION_MEMCACHED_SERVERS       => [ [ '127.0.0.1', 11211 ] ],
            // ConnectionOptions::OPTION_MEMCACHED_OPTIONS       => [ ],
            // ConnectionOptions::OPTION_MEMCACHED_ENDPOINTS_KEY => 'arangodb-php-endpoints'
            // ConnectionOptions::OPTION_MEMCACHED_TTL           => 600
        ];

        if ($config->getTraceFunction() !== null) {
            $connectionOptions[ArangoConnectionOptions::OPTION_TRACE] = $config->getTraceFunction();
        }

        if ($config->getAuthJwt() !== null) {
            $connectionOptions[ArangoConnectionOptions::OPTION_AUTH_JWT] = $config->getAuthJwt();
        }

        if (\str_starts_with($config->getAuthType(), 'ssl://')) {
            $connectionOptions[ArangoConnectionOptions::OPTION_VERIFY_CERT] = $config->isVerifyCert();
            $connectionOptions[ArangoConnectionOptions::OPTION_ALLOW_SELF_SIGNED] = $config->isSelfSigned();
            $connectionOptions[ArangoConnectionOptions::OPTION_CIPHERS] = $config->getCiphers();
        }

        $this->instance = new ArangoConnection($connectionOptions);
        $this->documentHandler = new ArangoDocumentHandler($this->instance);
        $this->collectionHandler = new ArangoCollectionHandler($this->instance);

        $collectionNames = array_keys($this->collectionHandler->getAllCollections());

        if ($config->isAutoCreate() && !\in_array($config->getCollection(), $collectionNames, true)) {
            return $this->createCollection($config->getCollection());
        }

        return $this->collectionHandler->has($config->getCollection());
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws ArangoException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        /** @var Config $config */
        $config = $this->getConfig();
        $options = [
            'overwriteMode' => 'replace',
            'returnNew' => true,
            'returnOld' => false,
            'silent' => false,
        ];

        $document = new ArangoDocument();
        $document->setInternalKey($item->getEncodedKey());
        $document->set(self::DRIVER_KEY_WRAPPER_INDEX, $item->getKey());
        $document->set(self::DRIVER_DATA_WRAPPER_INDEX, $this->encode($item->get()));
        $document->set(self::DRIVER_TAGS_WRAPPER_INDEX, $item->getTags());
        $document->set(self::DRIVER_EDATE_WRAPPER_INDEX, $item->getExpirationDate());
        $document->set(self::TTL_FIELD_NAME, $item->getExpirationDate()->getTimestamp());

        if ($config->isItemDetailedDate()) {
            $document->set(self::DRIVER_CDATE_WRAPPER_INDEX, $item->getCreationDate());
            $document->set(self::DRIVER_MDATE_WRAPPER_INDEX, $item->getModificationDate());
        }

        return $this->documentHandler->insert($config->getCollection(), $document, $options) !== null;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return null|array
     * @throws PhpfastcacheDriverException
     * @throws \Exception
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        /** @var Config $config */
        $config = $this->getConfig();

        try {
            $document = $this->documentHandler->get($config->getCollection(), $item->getEncodedKey());
        } catch (ArangoServerException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw new PhpfastcacheDriverException(
                'Got unexpeced error from Arangodb: ' . $e->getMessage()
            );
        }

        return $this->decode($document);
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $options = [
            'returnOld' => false
        ];

        try {
            $this->documentHandler->removeById($config->getCollection(), $item->getEncodedKey(), null, $options);
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
        /** @var Config $config */
        $config = $this->getConfig();

        try {
            $this->collectionHandler->truncate($config->getCollection());
            return true;
        } catch (ArangoException) {
            return false;
        }
    }

    /**
     * @throws PhpfastcacheDriverConnectException
     * @throws ArangoException
     */
    protected function createCollection($collectionName): bool
    {
        $collection = new ArangoCollection($collectionName);

        try {
            $this->collectionHandler->create($collection, [
                'type' => ArangoCollection::TYPE_DOCUMENT,
                'waitForSync' => false
            ]);

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
     * @return mixed
     * @throws \Exception
     */
    protected function decode(ArangoDocument $document): mixed
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $value = [
            self::DRIVER_KEY_WRAPPER_INDEX => $document->get(self::DRIVER_KEY_WRAPPER_INDEX),
            self::DRIVER_TAGS_WRAPPER_INDEX => $document->get(self::DRIVER_TAGS_WRAPPER_INDEX),
            self::DRIVER_DATA_WRAPPER_INDEX => \unserialize(
                $document->get(self::DRIVER_DATA_WRAPPER_INDEX),
                ['allowed_classes' => true]
            ),
        ];

        $eDate = $document->get(self::DRIVER_EDATE_WRAPPER_INDEX);
        $value[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX] = new \DateTime(
            $eDate['date'],
            new \DateTimeZone($eDate['timezone'])
        );

        if ($config->isItemDetailedDate()) {
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
        /** @var Config $config */
        $config = $this->getConfig();
        $rawData = [];

        $rawData['collectionCount'] = $this->collectionHandler->count($config->getCollection(), false);
        $rawData['collectionInfo'] = $this->collectionHandler->get($config->getCollection());

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

    public function getConfig() : Config|ConfigurationOption
    {
        return $this->config;
    }
}
