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
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Couchbase;

use Couchbase\Exception;
use Couchbase\PasswordAuthenticator;
use CouchbaseBucket;
use CouchbaseCluster as CouchbaseClient;
use CouchbaseException;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException};
use Psr\Cache\CacheItemInterface;


/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property CouchbaseClient $instance Instance of driver service
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
{
    use DriverBaseTrait;

    /**
     * @var CouchbaseBucket[]
     */
    protected $bucketInstances = [];

    /**
     * @var CouchbaseBucket
     */
    protected $bucketInstance;

    /**
     * @var string
     */
    protected $currentBucket = '';

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('couchbase');
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $info = $this->getBucket()->manager()->info();

        return (new DriverStatistic())
            ->setSize($info['basicStats']['diskUsed'])
            ->setRawData($info)
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo(
                'CouchBase version ' . $info['nodes'][0]['version'] . ', Uptime (in days): ' . round(
                    $info['nodes'][0]['uptime'] / 86400,
                    1
                ) . "\n For more information see RawData."
            );
    }

    /**
     * @return bool
     * @throws PhpfastcacheLogicException
     */
    protected function driverConnect(): bool
    {
        if ($this->instance instanceof CouchbaseClient) {
            throw new PhpfastcacheLogicException('Already connected to Couchbase server');
        }

        $clientConfig = $this->getConfig();


        $authenticator = new PasswordAuthenticator();
        $authenticator->username($clientConfig->getUsername())->password($clientConfig->getPassword());

        $this->instance = new CouchbaseClient(
            'couchbase://' . $clientConfig->getHost() . ($clientConfig->getPort() ? ":{$clientConfig->getPort()}" : '')
        );

        $this->instance->authenticate($authenticator);
        $this->setBucket($this->instance->openBucket($clientConfig->getBucketName()));

        return true;
    }

    /**
     * @param CouchbaseBucket $CouchbaseBucket
     */
    protected function setBucket(CouchbaseBucket $CouchbaseBucket)
    {
        $this->bucketInstance = $CouchbaseBucket;
    }

    /**
     * @param CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        try {
            /**
             * CouchbaseBucket::get() returns a CouchbaseMetaDoc object
             */
            return $this->decode($this->getBucket()->get($item->getEncodedKey())->value);
        } catch (CouchbaseException $e) {
            return null;
        }
    }

    /**
     * @return CouchbaseBucket
     */
    protected function getBucket(): CouchbaseBucket
    {
        return $this->bucketInstance;
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                return (bool)$this->getBucket()->upsert(
                    $item->getEncodedKey(),
                    $this->encode($this->driverPreWrap($item)),
                    ['expiry' => $item->getTtl()]
                );
            } catch (CouchbaseException $e) {
                return false;
            }
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                return (bool)$this->getBucket()->remove($item->getEncodedKey());
            } catch (Exception $e) {
                return $e->getCode() === COUCHBASE_KEY_ENOENT;
            }
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        $this->getBucket()->manager()->flush();
        return true;
    }
}
