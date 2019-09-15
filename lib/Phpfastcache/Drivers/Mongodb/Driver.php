<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Fabio Covolo Mazzo (fabiocmazzo) <fabiomazzo@gmail.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Mongodb;

use LogicException;
use MongoDB\{
    BSON\Binary, BSON\UTCDateTime, Client, Collection, Database, DeleteResult, Driver\Command, Driver\Exception\Exception as MongoDBException
};
use Phpfastcache\Core\Pool\{
    DriverBaseTrait, ExtendedCacheItemPoolInterface
};
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{
    PhpfastcacheDriverException, PhpfastcacheInvalidArgumentException
};
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property Client $instance Instance of driver service
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    const MONGODB_DEFAULT_DB_NAME = 'phpfastcache';

    use DriverBaseTrait;

    /**
     * @var Collection
     */
    public $collection;

    /**
     * @var Database
     */
    public $database;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        $mongoExtensionExists = class_exists(\MongoDB\Driver\Manager::class);

        if (!$mongoExtensionExists && class_exists(\MongoClient::class)) {
            \trigger_error('This driver is used to support the pecl MongoDb extension with mongo-php-library.
            For MongoDb with Mongo PECL support use Mongo Driver.', \E_USER_ERROR);
        }

        return $mongoExtensionExists && class_exists(\MongoDB\Collection::class);
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $document = $this->getCollection()->findOne(['_id' => $item->getEncodedKey()]);

        if ($document) {
            $return = [
                self::DRIVER_DATA_WRAPPER_INDEX => $this->decode($document[self::DRIVER_DATA_WRAPPER_INDEX]->getData()),
                self::DRIVER_TAGS_WRAPPER_INDEX => $this->decode($document[self::DRIVER_TAGS_WRAPPER_INDEX]->getData()),
                self::DRIVER_EDATE_WRAPPER_INDEX => (new \DateTime())->setTimestamp($document[self::DRIVER_EDATE_WRAPPER_INDEX]->toDateTime()->getTimestamp()),
            ];

            if (!empty($this->getConfig()->isItemDetailedDate())) {
                $return += [
                    self::DRIVER_MDATE_WRAPPER_INDEX => (new \DateTime())->setTimestamp($document[self::DRIVER_MDATE_WRAPPER_INDEX]->toDateTime()
                        ->getTimestamp()),
                    self::DRIVER_CDATE_WRAPPER_INDEX => (new \DateTime())->setTimestamp($document[self::DRIVER_CDATE_WRAPPER_INDEX]->toDateTime()
                        ->getTimestamp()),
                ];
            }

            return $return;
        }

        return null;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheDriverException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $set = [
                    self::DRIVER_DATA_WRAPPER_INDEX => new Binary($this->encode($item->get()), Binary::TYPE_GENERIC),
                    self::DRIVER_TAGS_WRAPPER_INDEX => new Binary($this->encode($item->getTags()), Binary::TYPE_GENERIC),
                    self::DRIVER_EDATE_WRAPPER_INDEX => ($item->getTtl() > 0 ? new UTCDateTime((\time() + $item->getTtl()) * 1000) : new UTCDateTime(\time() * 1000)),
                ];

                if (!empty($this->getConfig()->isItemDetailedDate())) {
                    $set += [
                        self::DRIVER_MDATE_WRAPPER_INDEX => ($item->getModificationDate() ? new UTCDateTime(($item->getModificationDate()
                                ->getTimestamp()) * 1000) : new UTCDateTime(\time() * 1000)),
                        self::DRIVER_CDATE_WRAPPER_INDEX => ($item->getCreationDate() ? new UTCDateTime(($item->getCreationDate()
                                ->getTimestamp()) * 1000) : new UTCDateTime(\time() * 1000)),
                    ];
                }
                $result = (array)$this->getCollection()->updateOne(
                    ['_id' => $item->getEncodedKey()],
                    [
                        '$set' => $set,
                    ],
                    ['upsert' => true, 'multiple' => false]
                );
            } catch (MongoDBException $e) {
                throw new PhpfastcacheDriverException('Got an exception while trying to write data to MongoDB server', 0, $e);
            }

            return isset($result['ok']) ? $result['ok'] == 1 : true;
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
            /**
             * @var DeleteResult $deletionResult
             */
            $deletionResult = $this->getCollection()->deleteOne(['_id' => $item->getEncodedKey()]);

            return $deletionResult->isAcknowledged();
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return $this->collection->deleteMany([])->isAcknowledged();
    }

    /**
     * @return bool
     * @throws MongodbException
     * @throws LogicException
     */
    protected function driverConnect(): bool
    {
        if ($this->instance instanceof Client) {
            throw new LogicException('Already connected to Mongodb server');
        }

        $timeout = $this->getConfig()->getTimeout() * 1000;
        $collectionName = $this->getConfig()->getCollectionName();
        $databaseName = $this->getConfig()->getDatabaseName();
        $driverOptions = $this->getConfig()->getDriverOptions();

        $this->instance = $this->instance ?: new Client($this->buildConnectionURI($databaseName), ['connectTimeoutMS' => $timeout], $driverOptions);
        $this->database = $this->database ?: $this->instance->selectDatabase($databaseName);

        if (!$this->collectionExists($collectionName)) {
            $this->database->createCollection($collectionName);
        }

        $this->collection = $this->database->selectCollection($collectionName);

        return true;
    }

    /**
     * Checks if a collection name exists on the Mongo database.
     *
     * @param string $collectionName The collection name to check.
     *
     * @return bool True if the collection exists, false if not.
     */
    protected function collectionExists($collectionName): bool
    {
        foreach ($this->database->listCollections() as $collection) {
            if ($collection->getName() === $collectionName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Builds the connection URI from the given parameters.
     *
     * @param string $databaseName
     * @return string The connection URI.
     */
    protected function buildConnectionURI($databaseName = ''): string
    {
        $servers = $this->getConfig()->getServers();
        $options = $this->getConfig()->getOptions();

        $protocol = $this->getConfig()->getProtocol();
        $host = $this->getConfig()->getHost();
        $port = $this->getConfig()->getPort();
        $username = $this->getConfig()->getUsername();
        $password = $this->getConfig()->getPassword();

        if( \count($servers) > 0 ){
            $host = \array_reduce($servers, static function($carry, $data){
                $carry .= ($carry === '' ? '' : ',').$data['host'].':'.$data['port'];
                return $carry;
            }, '');
            $port = false;
        }

        return \implode('', [
            "{$protocol}://",
            $username ?: '',
            $password ? ":{$password}" : '',
            $username ? '@' : '',
            $host,
            $port !== 27017 && $port !== false ? ":{$port}" : '',
            $databaseName ? "/{$databaseName}" : '',
            \count($options) > 0 ? '?'.\http_build_query($options) : '',
        ]);
    }

    /**
     * @return Collection
     */
    protected function getCollection(): Collection
    {
        return $this->collection;
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
        $serverStats = $this->instance->getManager()->executeCommand('phpFastCache', new Command([
            'serverStatus' => 1,
            'recordStats' => 0,
            'repl' => 0,
            'metrics' => 0,
        ]))->toArray()[0];

        $collectionStats = $this->instance->getManager()->executeCommand('phpFastCache', new Command([
            'collStats' => (isset($this->getConfig()['collectionName']) ? $this->getConfig()['collectionName'] : 'Cache'),
            'verbose' => true,
        ]))->toArray()[0];

        $array_filter_recursive = function ($array, callable $callback = null) use (&$array_filter_recursive) {
            $array = $callback($array);

            if (\is_object($array) || \is_array($array)) {
                foreach ($array as &$value) {
                    $value = \call_user_func($array_filter_recursive, $value, $callback);
                }
            }

            return $array;
        };

        $callback = function ($item) {
            /**
             * Remove unserializable properties
             */
            if ($item instanceof \MongoDB\BSON\UTCDateTime) {
                return (string)$item;
            }
            return $item;
        };

        $serverStats = $array_filter_recursive($serverStats, $callback);
        $collectionStats = $array_filter_recursive($collectionStats, $callback);

        $stats = (new DriverStatistic())
            ->setInfo('MongoDB version ' . $serverStats->version . ', Uptime (in days): ' . \round($serverStats->uptime / 86400,
                    1) . "\n For more information see RawData.")
            ->setSize($collectionStats->size)
            ->setData(\implode(', ', \array_keys($this->itemInstances)))
            ->setRawData([
                'serverStatus' => $serverStats,
                'collStats' => $collectionStats,
            ]);

        return $stats;
    }
}
