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

namespace Phpfastcache\Drivers\Couchbasev3;

use Couchbase\DocumentNotFoundException;
use Couchbase\Cluster as CouchbaseClient;
use Couchbase\Collection as CouchbaseCollection;
use Couchbase\Scope as CouchbaseScope;
use Couchbase\UpsertOptions;
use CouchbaseException;
use Phpfastcache\Drivers\Couchbase\Driver as CoubaseV2Driver;
use Phpfastcache\Drivers\Couchbase\Item;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{PhpfastcacheDriverCheckException, PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException};
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property CouchbaseClient $instance Instance of driver service
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver extends CoubaseV2Driver
{
    /**
     * @var CouchbaseScope
     */
    protected $scope;

    /**
     * @var CouchbaseCollection
     */
    protected $collection;

    /**
     * @return bool
     * @throws PhpfastcacheLogicException
     */
    protected function driverConnect(): bool
    {
        if (!\class_exists(\Couchbase\ClusterOptions::class)) {
            throw new PhpfastcacheDriverCheckException('You are using the Couchbase PHP SDK 2.x so please use driver Couchbasev3');
        }

        if ($this->instance instanceof CouchbaseClient) {
            throw new PhpfastcacheLogicException('Already connected to Couchbase server');
        }

        $connectionString = "couchbase://localhost";

        $options = new \Couchbase\ClusterOptions();
        $options->credentials($this->getConfig()->getUsername(), $this->getConfig()->getPassword());
        $this->instance = new \Couchbase\Cluster($connectionString, $options);

        $this->setBucket($this->instance->bucket($this->getConfig()->getBucketName()));
        $this->setScope($this->getBucket()->scope($this->getConfig()->getScopeName()));
        $this->setCollection($this->getScope()->collection($this->getConfig()->getCollectionName()));

        return true;
    }

    /**
     * @param CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        try {
            /**
             * CouchbaseBucket::get() returns a GetResult interface
             */
            return $this->decodeDocument((array)$this->getCollection()->get($item->getEncodedKey())->content());
        } catch (DocumentNotFoundException $e) {
            return null;
        }
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
                return (bool)$this->getCollection()->upsert(
                    $item->getEncodedKey(),
                    $this->encodeDocument($this->driverPreWrap($item)),
                    (new UpsertOptions())->expiry($item->getTtl())
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
                $this->getCollection()->remove($item->getEncodedKey());
                return true;
            } catch (DocumentNotFoundException $e) {
                return true;
            } catch (CouchbaseException $e) {
                return false;
            }
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        $this->instance->buckets()->flush($this->getConfig()->getBucketName());
        return true;
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        /**
         * Between SDK 2 and 3 we lost a lot of useful information :(
         * @see https://docs.couchbase.com/java-sdk/current/project-docs/migrating-sdk-code-to-3.n.html#management-apis
         */
        $info = $this->getBucket()->diagnostics(\bin2hex(\random_bytes(16)));

        return (new DriverStatistic())
            ->setSize(0)
            ->setRawData($info)
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo( $info['sdk'] . "\n For more information see RawData.");
    }

    /**
     * @return CouchbaseCollection
     */
    public function getCollection(): CouchbaseCollection
    {
        return $this->collection;
    }

    /**
     * @param CouchbaseCollection $collection
     * @return Driver
     */
    public function setCollection(CouchbaseCollection $collection): Driver
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @return CouchbaseScope
     */
    public function getScope(): CouchbaseScope
    {
        return $this->scope;
    }

    /**
     * @param CouchbaseScope $scope
     * @return Driver
     */
    public function setScope(CouchbaseScope $scope): Driver
    {
        $this->scope = $scope;
        return $this;
    }
}
