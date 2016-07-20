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

use MongoDB\Driver\Manager;
use LogicException;
use MongoDB\Collection;
use phpFastCache\Core\DriverAbstract;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use Psr\Cache\CacheItemInterface;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Binary;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    /**
     * @var MongodbManager
     */
    public $instance;
    
    /**
     * @var Collection
     */
    public $collection;

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
        if(!class_exists('MongoDB\Driver\Manager')){
            trigger_error('This driver is used to support the pecl MongoDb extension with mongo-php-library.<br />
            For MongoDb with Mongo PECL support use Mongo Driver.', E_USER_ERROR);
        }
        
      /*  if(!class_exists('MongoDB\Driver\Collection')){
        	trigger_error('The library mongo-php-library not found.<br />
            This driver do not support MonboDb low-level driver alone. Please install this driver to continue: https://github.com/mongodb/mongo-php-library', E_USER_ERROR);
        } */
 
        return extension_loaded('Mongodb');
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
                $result = (array) $this->getCollection()->updateMany(
                  ['_id' => $item->getKey()],
                  [
                    '$set' => [
                      self::DRIVER_TIME_WRAPPER_INDEX => ($item->getTtl() > 0 ? new UTCDateTime((time() + $item->getTtl()) * 1000) : new UTCDateTime(time()*1000)),
                      self::DRIVER_DATA_WRAPPER_INDEX => new Binary($this->encode($item->get()), Binary::TYPE_GENERIC),
                      self::DRIVER_TAGS_WRAPPER_INDEX => new Binary($this->encode($item->getTags()), Binary::TYPE_GENERIC),
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
              self::DRIVER_DATA_WRAPPER_INDEX => $this->decode($document[ self::DRIVER_DATA_WRAPPER_INDEX ]->getData()),
              self::DRIVER_TIME_WRAPPER_INDEX => (new \DateTime())->setTimestamp($document[ self::DRIVER_TIME_WRAPPER_INDEX ]->toDateTime()->getTimestamp()),
              self::DRIVER_TAGS_WRAPPER_INDEX => $this->decode($document[ self::DRIVER_TAGS_WRAPPER_INDEX ]->getData()),
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
            $deletionResult = (array) $this->getCollection()->deleteMany(['_id' => $item->getKey()], ["w" => 1]);
			// new driver has no results for deleteMany or deleteOne
            return true;
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return $this->getCollection()->drop();
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
            $port = isset($server[ 'port' ]) ? $server[ 'port' ] : '27017';
            $timeout = isset($server[ 'timeout' ]) ? $server[ 'timeout' ] : 3;
            $password = isset($this->config[ 'password' ]) ? $this->config[ 'password' ] : '';
            $username = isset($this->config[ 'username' ]) ? $this->config[ 'username' ] : '';


            /**
             * @todo make an url builder
             */
            $this->instance = $this->instance ?: (new \MongoDB\Driver\Manager('mongodb://' .
              ($username ?: '') .
              ($password ? ":{$password}" : '') .
              ($username ? '@' : '') . "{$host}" .
              ($port != '27017' ? ":{$port}" : ''), ['connectTimeoutMS' => $timeout * 1000]));
              $this->collection = $this->collection ?: new Collection($this->instance,'phpFastCache','Cache'); 
         }
    }


    /**
     * @return \Collection
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
     * @return driverStatistic
     */
    public function getStats()
    {
        $serverStatus = $this->instance->executeCommand(new Command([
          'serverStatus' => 1,
          'recordStats' => 0,
          'repl' => 0,
          'metrics' => 0,
        ]));

        $collStats = $this->instance->executeCommand(new Command([
          'collStats' => 'Cache',
          'verbose' => true,
        ]));

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