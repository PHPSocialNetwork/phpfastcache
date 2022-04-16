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

namespace Phpfastcache\Drivers\Couchbasev3;

use Couchbase\BaseException as CouchbaseException;
use Couchbase\Bucket as CouchbaseBucket;
use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\Collection;
use Couchbase\DocumentNotFoundException;
use Couchbase\Scope;
use Couchbase\UpsertOptions;
use DateTimeInterface;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * @property Cluster $instance Instance of driver service
 * @method Config getConfig()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait {
        __construct as __baseConstruct;
    }

    protected Scope $scope;

    protected Collection $collection;

    protected CouchbaseBucket $bucketInstance;

    public function __construct(ConfigurationOption $config, string $instanceId, EventManagerInterface $em)
    {
        $this->__baseConstruct($config, $instanceId, $em);
    }

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('couchbase');
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverCheckException
     */
    protected function driverConnect(): bool
    {
        if (!\class_exists(ClusterOptions::class)) {
            throw new PhpfastcacheDriverCheckException('You are using the Couchbase PHP SDK 2.x which is no longer supported in Phpfastcache v9');
        }

        $connectionString = "couchbase://{$this->getConfig()->getHost()}:{$this->getConfig()->getPort()}";

        $options = new ClusterOptions();
        $options->credentials($this->getConfig()->getUsername(), $this->getConfig()->getPassword());
        $this->instance = new Cluster($connectionString, $options);

        $this->setBucket($this->instance->bucket($this->getConfig()->getBucketName()));
        $this->setScope($this->getBucket()->scope($this->getConfig()->getScopeName()));
        $this->setCollection($this->getScope()->collection($this->getConfig()->getCollectionName()));

        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        try {
            /**
             * CouchbaseBucket::get() returns a GetResult interface
             */
            return $this->decodeDocument((array)$this->getCollection()->get($item->getEncodedKey())->content());
        } catch (DocumentNotFoundException) {
            return null;
        }
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        try {
            $this->getCollection()->upsert(
                $item->getEncodedKey(),
                $this->encodeDocument($this->driverPreWrap($item)),
                (new UpsertOptions())->expiry($item->getTtl())
            );
            return true;
        } catch (CouchbaseException) {
            return false;
        }
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        try {
            $this->getCollection()->remove($item->getEncodedKey());
            return true;
        } catch (DocumentNotFoundException) {
            return true;
        } catch (CouchbaseException) {
            return false;
        }
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
     * @throws \Exception
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
            ->setInfo($info['sdk'] . "\n For more information see RawData.");
    }

    /**
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * @param Collection $collection
     * @return Driver
     */
    public function setCollection(Collection $collection): Driver
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @return Scope
     */
    public function getScope(): Scope
    {
        return $this->scope;
    }

    /**
     * @param Scope $scope
     * @return Driver
     */
    public function setScope(Scope $scope): Driver
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @return CouchbaseBucket
     */
    protected function getBucket(): CouchbaseBucket
    {
        return $this->bucketInstance;
    }

    /**
     * @param CouchbaseBucket $couchbaseBucket
     */
    protected function setBucket(CouchbaseBucket $couchbaseBucket): void
    {
        $this->bucketInstance = $couchbaseBucket;
    }


    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function encodeDocument(array $data): array
    {
        $data[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX] = $this->encode($data[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX]);
        $data[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX] = $data[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX]
            ->format(DateTimeInterface::ATOM);

        if ($this->getConfig()->isItemDetailedDate()) {
            $data[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] = $data[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX]
                ->format(\DateTimeInterface::ATOM);

            $data[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] = $data[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX]
                ->format(\DateTimeInterface::ATOM);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function decodeDocument(array $data): array
    {
        $data[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX] = $this->decode($data[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX]);
        $data[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX] = \DateTime::createFromFormat(
            \DateTimeInterface::ATOM,
            $data[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX]
        );

        if ($this->getConfig()->isItemDetailedDate()) {
            $data[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] = \DateTime::createFromFormat(
                \DateTimeInterface::ATOM,
                $data[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX]
            );

            $data[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] = \DateTime::createFromFormat(
                \DateTimeInterface::ATOM,
                $data[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX]
            );
        }

        return $data;
    }
}
