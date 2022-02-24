<?php

/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Mongodb;

use LogicException;
use MongoClient;
use MongoDB\BSON\Binary;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use MongoDB\Driver\Manager;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * @property Client $instance Instance of driver service
 * @property Config $config Return the config object
 */
class Driver implements AggregatablePoolInterface, ExtendedCacheItemPoolInterface
{
    use TaggableCacheItemPoolTrait;
    public const MONGODB_DEFAULT_DB_NAME = 'phpfastcache'; // Public because used in config

    /**
     * @var Collection
     */
    public $collection;

    /**
     * @var Database
     */
    public $database;

    public function driverCheck(): bool
    {
        $mongoExtensionExists = class_exists(Manager::class);

        if (!$mongoExtensionExists && class_exists(MongoClient::class)) {
            trigger_error(
                'This driver is used to support the pecl MongoDb extension with mongo-php-library.
            For MongoDb with Mongo PECL support use Mongo Driver.',
                \E_USER_ERROR
            );
        }

        return $mongoExtensionExists && class_exists(Collection::class);
    }

    /**
     * @throws MongodbException
     * @throws LogicException
     */
    protected function driverConnect(): bool
    {
        $timeout = $this->getConfig()->getTimeout() * 1000;
        $collectionName = $this->getConfig()->getCollectionName();
        $databaseName = $this->getConfig()->getDatabaseName();
        $driverOptions = $this->getConfig()->getDriverOptions();

        $this->instance ??= new Client($this->buildConnectionURI($databaseName), ['connectTimeoutMS' => $timeout], $driverOptions);
        $this->database ??= $this->instance->selectDatabase($databaseName);

        if (!$this->collectionExists($collectionName)) {
            $this->database->createCollection($collectionName);
            $this->database->selectCollection($collectionName)
                ->createIndex(
                    [self::DRIVER_KEY_WRAPPER_INDEX => 1],
                    ['unique' => true, 'name' => 'unique_key_index']
                );
            $this->database->selectCollection($collectionName)
                ->createIndex(
                    [self::DRIVER_EDATE_WRAPPER_INDEX => 1],
                    ['expireAfterSeconds' => 0,  'name' => 'auto_expire_index']
                );
        }

        $this->collection = $this->database->selectCollection($collectionName);

        return true;
    }

    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $document = $this->getCollection()->findOne(['_id' => $this->getMongoDbItemKey($item)]);

        if ($document) {
            $return = [
                self::DRIVER_DATA_WRAPPER_INDEX => $this->decode($document[self::DRIVER_DATA_WRAPPER_INDEX]->getData()),
                self::DRIVER_TAGS_WRAPPER_INDEX => $document[self::DRIVER_TAGS_WRAPPER_INDEX]->jsonSerialize(),
                self::DRIVER_EDATE_WRAPPER_INDEX => $document[self::DRIVER_EDATE_WRAPPER_INDEX]->toDateTime(),
            ];

            if (!empty($this->getConfig()->isItemDetailedDate())) {
                $return += [
                    self::DRIVER_MDATE_WRAPPER_INDEX => $document[self::DRIVER_MDATE_WRAPPER_INDEX]->toDateTime(),
                    self::DRIVER_CDATE_WRAPPER_INDEX => $document[self::DRIVER_CDATE_WRAPPER_INDEX]->toDateTime(),
                ];
            }

            return $return;
        }

        return null;
    }

    /**
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     *
     * @return mixed
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        try {
            $set = [
                self::DRIVER_KEY_WRAPPER_INDEX => $item->getKey(),
                self::DRIVER_DATA_WRAPPER_INDEX => new Binary($this->encode($item->getRawValue()), Binary::TYPE_GENERIC),
                self::DRIVER_TAGS_WRAPPER_INDEX => $item->getTags(),
                self::DRIVER_EDATE_WRAPPER_INDEX => new UTCDateTime($item->getExpirationDate()),
            ];

            if (!empty($this->getConfig()->isItemDetailedDate())) {
                $set += [
                    self::DRIVER_MDATE_WRAPPER_INDEX => new UTCDateTime($item->getModificationDate()),
                    self::DRIVER_CDATE_WRAPPER_INDEX => new UTCDateTime($item->getCreationDate()),
                ];
            }
            $result = (array) $this->getCollection()->updateOne(
                ['_id' => $this->getMongoDbItemKey($item)],
                [
                    '$set' => $set,
                ],
                ['upsert' => true, 'multiple' => false]
            );
        } catch (MongoDBException $e) {
            throw new PhpfastcacheDriverException('Got an exception while trying to write data to MongoDB server: ' . $e->getMessage(), 0, $e);
        }

        return !isset($result['ok']) || 1 === (int) $result['ok'];
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        $deletionResult = $this->getCollection()->deleteOne(['_id' => $this->getMongoDbItemKey($item)]);

        return $deletionResult->isAcknowledged();
    }

    /**
     * @throws PhpfastcacheDriverException
     */
    protected function driverClear(): bool
    {
        try {
            return $this->collection->deleteMany([])->isAcknowledged();
        } catch (MongoDBException $e) {
            throw new PhpfastcacheDriverException('Got error while trying to empty the collection: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws MongoDBException
     */
    public function getStats(): DriverStatistic
    {
        $serverStats = $this->instance->getManager()->executeCommand(
            $this->getConfig()->getDatabaseName(),
            new Command(
                [
                    'serverStatus' => 1,
                    'recordStats' => 0,
                    'repl' => 0,
                    'metrics' => 0,
                ]
            )
        )->toArray()[0];

        $collectionStats = $this->instance->getManager()->executeCommand(
            $this->getConfig()->getDatabaseName(),
            new Command(
                [
                    'collStats' => $this->getConfig()->getCollectionName(),
                    'verbose' => true,
                ]
            )
        )->toArray()[0];

        $arrayFilterRecursive = static function ($array, ?callable $callback = null) use (&$arrayFilterRecursive) {
            $array = $callback($array);

            if (\is_object($array) || \is_array($array)) {
                foreach ($array as &$value) {
                    $value = $arrayFilterRecursive($value, $callback);
                }
            }

            return $array;
        };

        $callback = static function ($item) {
            /*
             * Remove unserializable properties
             */
            if ($item instanceof UTCDateTime) {
                return (string) $item;
            }

            return $item;
        };

        $serverStats = $arrayFilterRecursive($serverStats, $callback);
        $collectionStats = $arrayFilterRecursive($collectionStats, $callback);

        return (new DriverStatistic())
            ->setInfo(
                'MongoDB version ' . $serverStats->version . ', Uptime (in days): ' . round(
                    $serverStats->uptime / 86400,
                    1
                ) . "\n For more information see RawData."
            )
            ->setSize($collectionStats->size)
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData(
                [
                    'serverStatus' => $serverStats,
                    'collStats' => $collectionStats,
                ]
            );
    }

    protected function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * Builds the connection URI from the given parameters.
     *
     * @return string the connection URI
     */
    protected function buildConnectionURI(string $databaseName): string
    {
        $databaseName = urlencode($databaseName);
        $servers = $this->getConfig()->getServers();
        $options = $this->getConfig()->getOptions();

        $protocol = $this->getConfig()->getProtocol();
        $host = $this->getConfig()->getHost();
        $port = $this->getConfig()->getPort();
        $username = $this->getConfig()->getUsername();
        $password = $this->getConfig()->getPassword();

        if (\count($servers) > 0) {
            $host = array_reduce(
                $servers,
                static fn ($carry, $data) => $carry . ('' === $carry ? '' : ',') . $data['host'] . ':' . $data['port'],
                ''
            );
            $port = false;
        }

        return implode(
            '',
            [
                "{$protocol}://",
                $username ?: '',
                $password ? ":{$password}" : '',
                $username ? '@' : '',
                $host,
                27017 !== $port && false !== $port ? ":{$port}" : '',
                $databaseName ? "/{$databaseName}" : '',
                \count($options) > 0 ? '?' . http_build_query($options) : '',
            ]
        );
    }

    protected function getMongoDbItemKey(ExtendedCacheItemInterface $item): string
    {
        return 'pfc_' . $item->getEncodedKey();
    }

    /**
     * Checks if a collection name exists on the Mongo database.
     *
     * @param string $collectionName the collection name to check
     *
     * @return bool true if the collection exists, false if not
     */
    protected function collectionExists(string $collectionName): bool
    {
        foreach ($this->database->listCollections() as $collection) {
            if ($collection->getName() === $collectionName) {
                return true;
            }
        }

        return false;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
