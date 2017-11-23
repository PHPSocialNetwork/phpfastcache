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

namespace phpFastCache\Drivers\Couchbase;

use CouchbaseCluster as CouchbaseClient;
use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheLogicException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property CouchbaseClient $instance Instance of driver service
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

    /**
     * @var \CouchbaseBucket[]
     */
    protected $bucketInstances = [];

    /**
     * @var string
     */
    protected $bucketCurrent = '';

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
        return extension_loaded('Couchbase');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                return (bool)$this->getBucket()->upsert($item->getEncodedKey(), $this->encode($this->driverPreWrap($item)), ['expiry' => $item->getTtl()]);
            } catch (\CouchbaseException $e) {
                return false;
            }
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
        try {
            /**
             * CouchbaseBucket::get() returns a CouchbaseMetaDoc object
             */
            return $this->decode($this->getBucket()->get($item->getEncodedKey())->value);
        } catch (\CouchbaseException $e) {
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
            try {
                return (bool)$this->getBucket()->remove($item->getEncodedKey());
            } catch (\Couchbase\Exception $e) {
                return $e->getCode() === COUCHBASE_KEY_ENOENT;
            }
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        return $this->getBucket()->manager()->flush();
    }

    /**
     * @return bool
     * @throws phpFastCacheLogicException
     */
    protected function driverConnect()
    {
        if ($this->instance instanceof CouchbaseClient) {
            throw new phpFastCacheLogicException('Already connected to Couchbase server');
        } else {


            $host = isset($this->config[ 'host' ]) ? $this->config[ 'host' ] : '127.0.0.1';
            $port = isset($this->config[ 'port' ]) ? $this->config[ 'port' ] : 8091;
            $password = isset($this->config[ 'password' ]) ? $this->config[ 'password' ] : '';
            $username = isset($this->config[ 'username' ]) ? $this->config[ 'username' ] : '';
            $buckets = isset($this->config[ 'buckets' ]) ? $this->config[ 'buckets' ] : [
              [
                'bucket' => 'default',
                'password' => '',
              ],
            ];

            $this->instance = new CouchbaseClient("couchbase://{$host}:{$port}", $username, $password);

            foreach ($buckets as $bucket) {
                $this->bucketCurrent = $this->bucketCurrent ?: $bucket[ 'bucket' ];
                $this->setBucket($bucket[ 'bucket' ], $this->instance->openBucket($bucket[ 'bucket' ], $bucket[ 'password' ]));
            }
        }

        return true;
    }

    /**
     * @return \CouchbaseBucket
     */
    protected function getBucket()
    {
        return $this->bucketInstances[ $this->bucketCurrent ];
    }

    /**
     * @param $bucketName
     * @param \CouchbaseBucket $CouchbaseBucket
     * @throws phpFastCacheLogicException
     */
    protected function setBucket($bucketName, \CouchbaseBucket $CouchbaseBucket)
    {
        if (!array_key_exists($bucketName, $this->bucketInstances)) {
            $this->bucketInstances[ $bucketName ] = $CouchbaseBucket;
        } else {
            throw new phpFastCacheLogicException('A bucket instance with this name already exists.');
        }
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
        $info = $this->getBucket()->manager()->info();

        return (new DriverStatistic())
          ->setSize($info[ 'basicStats' ][ 'diskUsed' ])
          ->setRawData($info)
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setInfo('CouchBase version ' . $info[ 'nodes' ][ 0 ][ 'version' ] . ', Uptime (in days): ' . round($info[ 'nodes' ][ 0 ][ 'uptime' ] / 86400,
              1) . "\n For more information see RawData.");
    }
}