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
use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    /**
     * @var CouchbaseClient
     */
    public $instance;

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
     * @throws \InvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            return $this->getBucket()->upsert(md5($item->getKey()), $this->encode($this->driverPreWrap($item)), ['expiry' => $item->getTtl()]);
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
        try {
            /**
             * CouchbaseBucket::get() returns a CouchbaseMetaDoc object
             */
            return $this->decode($this->getBucket()->get(md5($item->getKey()))->value);
        } catch (\CouchbaseException $e) {
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
            return $this->getBucket()->remove(md5($item->getKey()));
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
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
     */
    protected function driverConnect()
    {
        if ($this->instance instanceof CouchbaseClient) {
            throw new \LogicException('Already connected to Couchbase server');
        } else {


            $host = isset($this->config[ 'host' ]) ? $this->config[ 'host' ] : '127.0.0.1';
            //$port = isset($server[ 'port' ]) ? $server[ 'port' ] : '11211';
            $password = isset($this->config[ 'password' ]) ? $this->config[ 'password' ] : '';
            $username = isset($this->config[ 'username' ]) ? $this->config[ 'username' ] : '';
            $buckets = isset($this->config[ 'buckets' ]) ? $this->config[ 'buckets' ] : [
              [
                'bucket' => 'default',
                'password' => '',
              ],
            ];

            $this->instance = $this->instance ?: new CouchbaseClient("couchbase://{$host}", $username, $password);

            foreach ($buckets as $bucket) {
                $this->bucketCurrent = $this->bucketCurrent ?: $bucket[ 'bucket' ];
                $this->setBucket($bucket[ 'bucket' ], $this->instance->openBucket($bucket[ 'bucket' ], $bucket[ 'password' ]));
            }
        }
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
     * @throws \LogicException
     */
    protected function setBucket($bucketName, \CouchbaseBucket $CouchbaseBucket)
    {
        if (!array_key_exists($bucketName, $this->bucketInstances)) {
            $this->bucketInstances[ $bucketName ] = $CouchbaseBucket;
        } else {
            throw new \LogicException('A bucket instance with this name already exists.');
        }
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
        $info = $this->getBucket()->manager()->info();

        return (new driverStatistic())
          ->setSize($info[ 'basicStats' ][ 'diskUsed' ])
          ->setRawData($info)
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setInfo('CouchBase version ' . $info[ 'nodes' ][ 0 ][ 'version' ] . ', Uptime (in days): ' . round($info[ 'nodes' ][ 0 ][ 'uptime' ] / 86400, 1) . "\n For more information see RawData.");
    }
}