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

namespace phpFastCache\Drivers\Mongodb;

use LogicException;
use MongoBinData;
use MongoClient as MongodbClient;
use MongoCollection;
use MongoConnectionException;
use MongoCursorException;
use MongoDate;
use phpFastCache\Core\DriverAbstract;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property MongodbClient $instance Instance of driver service
 */
class Driver extends DriverAbstract
{
    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
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
        return class_exists('MongoClient');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $result = (array) $this->getCollection()->update(
                  ['_id' => $item->getKey()],
                  [
                    '$set' => [
                      self::DRIVER_TIME_WRAPPER_INDEX => ($item->getTtl() > 0 ? new MongoDate(time() + $item->getTtl()) : new MongoDate(time())),
                      self::DRIVER_DATA_WRAPPER_INDEX => new MongoBinData($this->encode($item->get()), MongoBinData::BYTE_ARRAY),
                      self::DRIVER_TAGS_WRAPPER_INDEX => new MongoBinData($this->encode($item->getTags()), MongoBinData::BYTE_ARRAY),
                    ],
                  ],
                  ['upsert' => true, 'multiple' => false]
                );
            } catch (MongoCursorException $e) {
                return false;
            }

            return isset($result[ 'ok' ]) ? $result[ 'ok' ] == 1 : true;
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    protected function driverRead(CacheItemInterface $item)
    {
        $document = $this->getCollection()
          ->findOne(['_id' => $item->getKey()],
            [self::DRIVER_DATA_WRAPPER_INDEX, self::DRIVER_TIME_WRAPPER_INDEX, self::DRIVER_TAGS_WRAPPER_INDEX  /*'d', 'e'*/]);

        if ($document) {
            return [
              self::DRIVER_DATA_WRAPPER_INDEX => $this->decode($document[ self::DRIVER_DATA_WRAPPER_INDEX ]->bin),
              self::DRIVER_TIME_WRAPPER_INDEX => (new \DateTime())->setTimestamp($document[ self::DRIVER_TIME_WRAPPER_INDEX ]->sec),
              self::DRIVER_TAGS_WRAPPER_INDEX => $this->decode($document[ self::DRIVER_TAGS_WRAPPER_INDEX ]->bin),
            ];
        } else {
            return null;
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $deletionResult = (array) $this->getCollection()->remove(['_id' => $item->getKey()], ["w" => 1]);

            return (int) $deletionResult[ 'ok' ] === 1 && !$deletionResult[ 'err' ];
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return (bool) $this->getCollection()->drop()['ok'];
    }

    /**
     * @return bool
     * @throws MongoConnectionException
     * @throws LogicException
     */
    protected function driverConnect()
    {
        if ($this->instance instanceof MongodbClient) {
            throw new LogicException('Already connected to Mongodb server');
        } else {
            $host = isset($this->config[ 'host' ]) ? $this->config[ 'host' ] : '127.0.0.1';
            $port = isset($this->config[ 'port' ]) ? $this->config[ 'port' ] : '27017';
            $timeout = isset($this->config[ 'timeout' ]) ? $this->config[ 'timeout' ] : 3;
            $password = isset($this->config[ 'password' ]) ? $this->config[ 'password' ] : '';
            $username = isset($this->config[ 'username' ]) ? $this->config[ 'username' ] : '';


            /**
             * @todo make an url builder
             */
            $this->instance = $this->instance ?: (new MongodbClient('mongodb://' .
              ($username ?: '') .
              ($password ? ":{$password}" : '') .
              ($username ? '@' : '') . "{$host}" .
              ($port != '27017' ? ":{$port}" : ''), ['connectTimeoutMS' => $timeout * 1000]))->phpFastCache;
            // $this->instance->Cache->createIndex([self::DRIVER_TIME_WRAPPER_INDEX => 1], ['expireAfterSeconds' => 0]);
        }
    }


    /**
     * @return \MongoCollection
     */
    protected function getCollection()
    {
        return $this->instance->Cache;
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return driverStatistic
     */
    public function getStats()
    {
        $serverStatus = $this->getCollection()->db->command([
          'serverStatus' => 1,
          'recordStats' => 0,
          'repl' => 0,
          'metrics' => 0,
        ]);

        $collStats = $this->getCollection()->db->command([
          'collStats' => 'Cache',
          'verbose' => true,
        ]);

        $stats = (new driverStatistic())
          ->setInfo('MongoDB version ' . $serverStatus[ 'version' ] . ', Uptime (in days): ' . round($serverStatus[ 'uptime' ] / 86400, 1) . "\n For more information see RawData.")
          ->setSize((int) @$collStats[ 'size' ])
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setRawData([
            'serverStatus' => $serverStatus,
            'collStats' => $collStats,
          ]);

        return $stats;
    }
}
