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
 * @author Fabio Covolo Mazzo (fabiocmazzo) <fabiomazzo@gmail.com>
 *
 */

namespace phpFastCache\Drivers\Mongodb;

use LogicException;
use MongoDB\BSON\Binary;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use MongoDB\Driver\Manager as MongodbManager;
use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property MongodbManager $instance Instance of driver service
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

    /**
     * @var Collection
     */
    public $collection;

    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverCheckException
     */
    public function __construct(array $config = [])
    {
        $this->setup($config);

        if (!$this->driverCheck()) {
            throw new phpFastCacheDriverCheckException(sprintf(self::DRIVER_CHECK_FAILURE, $this->getDriverName()));
        } else {
            $this->driverConnect();
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        if (!class_exists('MongoDB\Driver\Manager') && class_exists('MongoClient')) {
            trigger_error('This driver is used to support the pecl MongoDb extension with mongo-php-library.
            For MongoDb with Mongo PECL support use Mongo Driver.', E_USER_ERROR);
        }

        return class_exists('MongoDB\Collection');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheInvalidArgumentException
     * @throws phpFastCacheDriverException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $set = [
                  self::DRIVER_DATA_WRAPPER_INDEX => new Binary($this->encode($item->get()), Binary::TYPE_GENERIC),
                  self::DRIVER_TAGS_WRAPPER_INDEX => new Binary($this->encode($item->getTags()), Binary::TYPE_GENERIC),
                  self::DRIVER_EDATE_WRAPPER_INDEX => ($item->getTtl() > 0 ? new UTCDateTime((time() + $item->getTtl()) * 1000) : new UTCDateTime(time() * 1000)),
                ];

                if(!empty($this->config[ 'itemDetailedDate' ])){
                    $set += [
                      self::DRIVER_MDATE_WRAPPER_INDEX => ($item->getModificationDate() ? new UTCDateTime(($item->getModificationDate()->getTimestamp()) * 1000) : new UTCDateTime(time() * 1000)),
                      self::DRIVER_CDATE_WRAPPER_INDEX => ($item->getCreationDate() ? new UTCDateTime(($item->getCreationDate()->getTimestamp()) * 1000) : new UTCDateTime(time() * 1000)),
                    ];
                }

                $result = (array)$this->getCollection()->updateOne(
                  ['_id' => $item->getEncodedKey()],
                  ['$set' => $set],
                  ['upsert' => true, 'multiple' => false]
                );
            } catch (MongoDBException $e) {
                throw new phpFastCacheDriverException('Got an exception while trying to write data to MongoDB server', null, $e);
            }

            return isset($result[ 'ok' ]) ? $result[ 'ok' ] == 1 : true;
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
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
              self::DRIVER_DATA_WRAPPER_INDEX => $this->decode($document[ self::DRIVER_DATA_WRAPPER_INDEX ]->getData()),
              self::DRIVER_TAGS_WRAPPER_INDEX => $this->decode($document[ self::DRIVER_TAGS_WRAPPER_INDEX ]->getData()),
              self::DRIVER_EDATE_WRAPPER_INDEX => (new \DateTime())->setTimestamp($document[ self::DRIVER_EDATE_WRAPPER_INDEX ]->toDateTime()->getTimestamp()),
            ];

            if(!empty($this->config[ 'itemDetailedDate' ])){
                $return += [
                  self::DRIVER_MDATE_WRAPPER_INDEX => (new \DateTime())->setTimestamp($document[ self::DRIVER_MDATE_WRAPPER_INDEX ]->toDateTime()->getTimestamp()),
                  self::DRIVER_CDATE_WRAPPER_INDEX => (new \DateTime())->setTimestamp($document[ self::DRIVER_CDATE_WRAPPER_INDEX ]->toDateTime()->getTimestamp()),
                ];
            }

            return $return;
        } else {
            return null;
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item)
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
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        /**
         * @var \MongoDB\Model\BSONDocument $result
         */
        $result = $this->getCollection()->drop()->getArrayCopy();
        $this->collection = new Collection($this->instance, 'phpFastCache', 'Cache');

        /**
         * This will rebuild automatically the Collection indexes
         */
        $this->save($this->getItem('__PFC_CACHE_CLEARED__')->set(true));

        return !empty($result[ 'ok' ]);
    }

    /**
     * @return bool
     * @throws MongodbException
     * @throws LogicException
     */
    protected function driverConnect()
    {
        if ($this->instance instanceof \MongoDB\Driver\Manager) {
            throw new LogicException('Already connected to Mongodb server');
        } else {
            $host = isset($this->config[ 'host' ]) ? $this->config[ 'host' ] : '127.0.0.1';
            $port = isset($this->config[ 'port' ]) ? $this->config[ 'port' ] : '27017';
            $timeout = isset($this->config[ 'timeout' ]) ? $this->config[ 'timeout' ] : 3;
            $password = isset($this->config[ 'password' ]) ? $this->config[ 'password' ] : '';
            $username = isset($this->config[ 'username' ]) ? $this->config[ 'username' ] : '';
            $collectionName = isset($this->config[ 'collectionName' ]) ? $this->config[ 'collectionName' ] : 'Cache';
            $databaseName = isset($this->config[ 'databaseName' ]) ? $this->config[ 'databaseName' ] : 'phpFastCache';


            /**
             * @todo make an url builder
             */
            $this->instance = $this->instance ?: (new MongodbManager('mongodb://' .
              ($username ?: '') .
              ($password ? ":{$password}" : '') .
              ($username ? '@' : '') . "{$host}" .
              ($port != '27017' ? ":{$port}" : ''), ['connectTimeoutMS' => $timeout * 1000]));
            $this->collection = $this->collection ?: new Collection($this->instance, $databaseName, $collectionName);

            return true;
        }
    }


    /**
     * @return Collection
     */
    protected function getCollection()
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
    public function getStats()
    {
        $serverStats = $this->instance->executeCommand('phpFastCache', new Command([
          'serverStatus' => 1,
          'recordStats' => 0,
          'repl' => 0,
          'metrics' => 0,
        ]))->toArray()[ 0 ];

        $collectionStats = $this->instance->executeCommand('phpFastCache', new Command([
          'collStats' => (isset($this->config[ 'collectionName' ]) ? $this->config[ 'collectionName' ] : 'Cache'),
          'verbose' => true,
        ]))->toArray()[ 0 ];

        $array_filter_recursive = function ($array, callable $callback = null) use (&$array_filter_recursive) {
            $array = $callback($array);

            if (is_object($array) || is_array($array)) {
                foreach ($array as &$value) {
                    $value = call_user_func($array_filter_recursive, $value, $callback);
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
          ->setInfo('MongoDB version ' . $serverStats->version . ', Uptime (in days): ' . round($serverStats->uptime / 86400,
              1) . "\n For more information see RawData.")
          ->setSize($collectionStats->size)
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setRawData([
            'serverStatus' => $serverStats,
            'collStats' => $collectionStats,
          ]);

        return $stats;
    }
}
